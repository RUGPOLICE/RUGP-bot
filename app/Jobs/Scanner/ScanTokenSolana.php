<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Jobs\Middleware\Localized;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Dex;
use App\Models\Pool;
use App\Models\Token;
use App\Services\GeckoTerminalService;
use App\Services\Network\GoplusService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class ScanTokenSolana implements ShouldQueue
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
    public function handle(GeckoTerminalService $geckoTerminalService, GoplusService $goplusService): void
    {
        if (!$this->token->scanned_at || $this->token->scanned_at <= now()->subDay() || !$this->token->is_revoked) {

            $data = $goplusService->getSolanaLikeData($this->token->address);
            if (!$data) throw new MetadataError($this->token);

            $info = $geckoTerminalService->getToken($this->token->address, $this->token->network);
            if ($info) $this->token->update($info);

            $this->token->update($data['token']);
            $this->token->scanned_at = now();
            $this->token->save();

            $pool = $geckoTerminalService->getPoolsByTokenAddress($this->token->address, $this->token->network->slug);
            if (!$pool) throw new MetadataError($this->token);

            $this->token->pools()->whereNot('address', $pool['address'])->delete();

            $dex = Dex::query()->updateOrCreate(['slug' => $pool['dex']]);
            unset($pool['dex']);

            $pool['token_id'] = $this->token->id;
            $pool['dex_id'] = $dex->id;
            $pool += $data['pool'];

            Pool::query()->updateOrCreate(['address' => $pool['address']], $pool);
            $this->updateStatistics();

        }
    }

    private function updateStatistics(): void
    {
        $this->token->is_warn_honeypot = $this->checkHoneypot();
        $this->token->is_warn_rugpull = $this->checkRugpull();
        $this->token->is_warn_original = $this->checkOriginal();
        $this->token->is_warn_scam = $this->checkScam();
        $this->token->is_warn_liquidity = $this->checkLiquidity();

        $this->token->sendNotification($this->source);
        $this->token->save();
    }


    private function checkHoneypot(): bool
    {
        // Already checked
        return $this->token->is_warn_honeypot;
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
}
