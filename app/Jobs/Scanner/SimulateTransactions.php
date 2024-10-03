<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\SimulationError;
use App\Jobs\Middleware\Localized;
use App\Models\Token;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class SimulateTransactions implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(public Token $token, public ?Language $language = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(): void
    {
        $failed = $this->token->pools()->where(function (Builder $query) { $query->whereNull('tax_buy')->orWhereNull('tax_sell'); })->exists();
        if (!$this->token->is_revoked || $failed) {

            @[$taxes, $message] = $this->token->network->service->getTaxes($this->token->address, $this->token->network->slug);
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

        }
    }
}
