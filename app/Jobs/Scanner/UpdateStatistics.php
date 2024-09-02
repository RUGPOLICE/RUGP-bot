<?php

namespace App\Jobs\Scanner;

use App\Enums\Dex;
use App\Enums\Language;
use App\Jobs\Middleware\Localized;
use App\Models\Token;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;

class UpdateStatistics implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(public Token $token, public ?Language $language = null) {}

    public function middleware(): array
    {
        return [new Localized];
    }

    public function handle(): void
    {
        $this->token->is_warn_honeypot = $this->checkHoneypot();
        $this->token->is_warn_rugpull = $this->checkRugpull();
        $this->token->is_warn_original = $this->checkOriginal();
        $this->token->is_warn_scam = $this->checkScam();
        $this->token->is_warn_liquidity_stonfi = $this->checkLiquidityStonfi();
        $this->token->is_warn_liquidity_dedust = $this->checkLiquidityDedust();
        $this->token->is_warn_liquidity = $this->checkLiquidity();
        $this->token->is_warn_burned = $this->checkBurned();
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
