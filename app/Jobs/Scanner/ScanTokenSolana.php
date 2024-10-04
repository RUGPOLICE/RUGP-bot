<?php

namespace App\Jobs\Scanner;

use App\Enums\Dex;
use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Exceptions\SimulationError;
use App\Jobs\Middleware\Localized;
use App\Models\Chat;
use App\Models\Pool;
use App\Models\Token;
use App\Services\GeckoTerminalService;
use App\Services\Network\TonService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScanTokenSolana implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public ?Language $language = null,
        public ?Chat $source = null,
    ) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    /**
     * @throws MetadataError
     */
    public function handle(GeckoTerminalService $geckoTerminalService, TonService $tonService): void
    {
        $this->updateMetadata($tonService);
        $this->updatePools($geckoTerminalService, $tonService);
        $this->updateHolders($tonService);
        $this->updateLiquidity($tonService);
        $this->simulateTransactions($tonService);
        $this->updateStatistics();
    }


    /**
     * @throws MetadataError
     */
    private function updateMetadata(TonService $tonService): void
    {
        if (!$this->token->scanned_at || $this->token->scanned_at <= now()->subDay() || !$this->token->is_revoked) {

            $tokenMetadata = $tonService->getJetton($this->token->address);
            if (!$tokenMetadata) throw new MetadataError($this->token);

            $this->token->update($tokenMetadata);
            $this->token->scanned_at = now();
            $this->token->save();

        }
    }

    /**
     * @throws MetadataError
     */
    private function updatePools(GeckoTerminalService $geckoTerminalService, TonService $tonService): void
    {
        $tokenMetadata = $geckoTerminalService->getTokenInfo($this->token->address, $this->token->network->slug);
        if (!$tokenMetadata) throw new MetadataError($this->token);

        $this->token->update($tokenMetadata);
        foreach ($geckoTerminalService->getPoolsByTokenAddress($this->token->address, $this->token->network->slug) as $pool) {

            $key = "update-pools:{$pool['address']}";
            if (!Cache::has($key)) {

                Cache::set($key, 'scanned', 60 * 10);

                $poolMetadata = $tonService->getJetton($pool['address']);
                if (!$poolMetadata) throw new MetadataError($this->token);

                Pool::query()->updateOrCreate(
                    ['address' => $pool['address']],
                    $pool + ['token_id' => $this->token->id, 'supply' => $poolMetadata['supply']]
                );

            }

        }
    }

    private function updateHolders(TonService $tonService): void
    {
        $key = "update-holders:{$this->token->address}";
        if (!Cache::has($key)) {

            Cache::set($key, 'scanned', 60 * 10);

            try {

                $tokenHolders = $tonService->getJettonHolders($this->token->address, $this->token->supply);
                if ($tokenHolders) [$this->token->holders, $this->token->holders_count] = $tokenHolders;

            } catch (Throwable $e) {

                Log::error($e);

            }

        }
    }

    private function updateLiquidity(TonService $tonService): void
    {
        foreach ($this->token->pools as $pool) {

            $key = "update-liquidity:$pool->address";
            if (!Cache::has($key)) {

                Cache::set($key, 'scanned', 60 * 10);

                try {

                    $poolHolders = $tonService->getJettonHolders($pool->address, $pool->supply, 4);
                    if ($poolHolders) {

                        [$poolHolders, $holdersCount] = $poolHolders;
                        if ($holdersCount) $pool->update($tonService->getLock($pool->address, $pool->supply, $poolHolders));

                    }

                } catch (Throwable $e) {

                    Log::error($e);

                }

            }

        }
    }

    private function simulateTransactions(TonService $tonService): void
    {
        $failed = $this->token->pools()->where(function (Builder $query) { $query->whereNull('tax_buy')->orWhereNull('tax_sell'); })->exists();
        if (!$this->token->is_revoked || $failed) {

            try {

                @[$taxes, $message] = $tonService->getTaxes($this->token->address);
                if (!$taxes)
                    throw new SimulationError($this->token, $message);

                $this->token->is_known_master = $taxes['is_known_master'];
                $this->token->is_known_wallet = $taxes['is_known_wallet'];
                $this->token->save();

                foreach ($this->token->pools as $pool) {

                    $pool->tax_buy = isset($taxes->{$pool->dex->value}->taxBuy) ? ($taxes->{$pool->dex->value}->taxBuy * 100) : $pool->tax_buy;
                    $pool->tax_sell = isset($taxes->{$pool->dex->value}->taxSell) ? ($taxes->{$pool->dex->value}->taxSell * 100) : $pool->tax_sell;
                    $pool->tax_transfer = isset($taxes->{$pool->dex->value}->taxTransfer) ? ($taxes->{$pool->dex->value}->taxTransfer * 100) : $pool->tax_transfer;
                    $pool->save();

                }

            } catch (Throwable $e) {

                Log::error($e);

            }

        }
    }

    private function updateStatistics(): void
    {
        $this->token->is_warn_honeypot = $this->checkHoneypot();
        $this->token->is_warn_rugpull = $this->checkRugpull();
        $this->token->is_warn_original = $this->checkOriginal();
        $this->token->is_warn_scam = $this->checkScam();
        $this->token->is_warn_liquidity_stonfi = $this->checkLiquidityStonfi();
        $this->token->is_warn_liquidity_dedust = $this->checkLiquidityDedust();
        $this->token->is_warn_liquidity = $this->checkLiquidity();
        $this->token->is_warn_burned = $this->checkBurned();

        if ($this->token->isDirty(['is_warn_honeypot', 'is_warn_rugpull', 'is_warn_scam'])) {
            $delay = now();
            foreach (Chat::query()->where('is_show_scam', true)->whereNot('id', $this->source?->id)->get() as $chat)
                SendScamPost::dispatch($this->token, $chat, $chat->language)->delay($delay = $delay->addSecond());
        }

        $this->token->save();
    }


    private function checkHoneypot(): bool
    {
        return $this->token->pools()->where('tax_sell', '<', 0)->exists();
    }

    private function checkRugpull(): bool
    {
        $holders = $this->token->holders?->slice(0, 10)->filter(fn ($holder) => str_contains($holder['name'], 'MEXC') || str_contains($holder['name'], 'Bybit') || str_contains($holder['name'], 'OKX'))->count();
        return $this->token->holders_count < 10000 && $holders;
    }

    private function checkOriginal(): bool
    {
        // Already checked
        return $this->token->is_warn_original;
    }

    private function checkScam(): bool
    {
        return $this->token->pools()->where('tax_sell', '>=', 90)->exists();
    }

    private function checkLiquidityStonfi(): bool
    {
        return $this->token->pools()
            ->where('dex', Dex::STONFI)
            ->where('reserve', '<', 500)
            ->whereNull('tax_sell')
            ->exists();
    }

    private function checkLiquidityDedust(): bool
    {
        return $this->token->pools()
            ->where('dex', Dex::DEDUST)
            ->where('reserve', '<', 500)
            ->exists();
    }

    private function checkLiquidity(): bool
    {
        return $this->token->pools()->where('reserve', '<', 5)->exists();
    }

    private function checkBurned(): bool
    {
        return !$this->token->pools()
            ->where(function (Builder $query) {
                $query->where('burned_percent', '>=', 95);
                $query->orWhere(function (Builder $query) {
                    $query->where('locked_percent', '>=', 95);
                    $query->where(function (Builder $query) {
                        $query->where('unlocks_at', '>', now());
                        $query->orWhereNull('unlocks_at');
                    });
                });
            })
            ->exists();
    }
}
