<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Exceptions\SimulationError;
use App\Jobs\Middleware\Localized;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Dex;
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

class ScanTokenTon implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public ?Language $language = null,
        public Chat|Account|null $source = null,
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

        $pool = $geckoTerminalService->getPoolsByTokenAddress($this->token->address, $this->token->network->slug);
        if (!$pool) throw new MetadataError($this->token);

        $poolMetadata = $tonService->getJetton($pool['address']);
        if (!$poolMetadata) throw new MetadataError($this->token);

        $this->token->pools()->whereNot('address', $pool['address'])->delete();

        $dex = Dex::query()->updateOrCreate(['slug' => $pool['dex']]);
        unset($pool['dex']);

        $pool['token_id'] = $this->token->id;
        $pool['dex_id'] = $dex->id;
        $pool['supply'] = $poolMetadata['supply'];
        $pool['decimals'] = $poolMetadata['decimals'];

        Pool::query()->updateOrCreate(['address' => $pool['address']], $pool);
    }

    private function updateHolders(TonService $tonService): void
    {
        $key = "update-holders:{$this->token->id}";
        if (!Cache::has($key)) {

            Cache::set($key, 'scanned', 60 * 10);

            try {

                $tokenHolders = $tonService->getJettonHolders($this->token->address, $this->token->supply, $this->token->decimals);
                if ($tokenHolders) [$this->token->holders, $this->token->holders_count] = $tokenHolders;

            } catch (Throwable $e) {

                Log::error($e);

            }

        }
    }

    private function updateLiquidity(TonService $tonService): void
    {
        $pool = $this->token->pools()->first();
        $key = "update-liquidity:$pool->id";

        if (!Cache::has($key)) {

            Cache::set($key, 'scanned', 60 * 10);

            try {

                $poolHolders = $tonService->getJettonHolders($pool->address, $pool->supply, $pool->decimals, 4);
                if ($poolHolders) {

                    [$poolHolders, $holdersCount] = $poolHolders;
                    if ($holdersCount) $pool->update($tonService->getLock($pool->supply, $pool->decimals, $poolHolders));

                }

            } catch (Throwable $e) {

                Log::error($e);

            }

        }
    }

    private function simulateTransactions(TonService $tonService): void
    {
        $pool = $this->token->pools()->first();
        if (!$this->token->is_revoked || $pool->tax_buy === null || $pool->tax_sell === null) {

            $taxes = $tonService->getTaxes($this->token->address, $pool->dex->slug);
            if (gettype($taxes) === 'string') throw new SimulationError($this->token, $taxes);

            $this->token->is_known_master = $taxes['is_known_master'];
            $this->token->is_known_wallet = $taxes['is_known_wallet'];
            $this->token->save();

            $pool->tax_buy = $taxes['tax_buy'] ?? $pool->tax_buy;
            $pool->tax_sell = $taxes['tax_sell'] ?? $pool->tax_sell;
            $pool->tax_transfer = $taxes['tax_transfer'] ?? $pool->tax_transfer;
            $pool->save();

        }
    }

    private function updateStatistics(): void
    {
        $this->token->is_warn_honeypot = $this->token->is_warn_honeypot || $this->checkHoneypot();
        $this->token->is_warn_rugpull = $this->checkRugpull();
        $this->token->is_warn_original = $this->checkOriginal();
        $this->token->is_warn_scam = $this->token->is_warn_scam || $this->checkScam();
        $this->token->is_warn_liquidity = $this->checkLiquidity();
        $this->token->is_warn_burned = $this->checkBurned();

        $this->token->sendNotification($this->source);
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

    private function checkLiquidity(): bool
    {
        return $this->token->pools()->where('reserve', '<', 500)->exists();
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
