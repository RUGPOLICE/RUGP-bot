<?php

namespace App\Jobs\Scanner;

use App\Enums\Dex;
use App\Exceptions\SimulationError;
use App\Jobs\Middleware\Localized;
use App\Models\Account;
use App\Models\Token;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class SimulateTransactions implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public function __construct(public Token $token, public ?Account $account = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(): void
    {
        if (!$this->token->is_revoked) {

            Log::info($this->token->address);
            $dex = implode(',', Dex::all());
            $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/main.js {$this->token->address} $dex");
            $report = json_decode($result->output());

            if (!$report->success)
                throw new SimulationError($this->token);

            $this->token->is_known_master = $report->isKnownMaster;
            $this->token->is_known_wallet = $report->isKnownWallet;

            foreach ($this->token->pools as $pool) {

                $pool->tax_buy = isset($report->{$pool->dex->value}->taxBuy) ? ($report->{$pool->dex->value}->taxBuy * 100) : $pool->tax_buy;
                $pool->tax_sell = isset($report->{$pool->dex->value}->taxSell) ? ($report->{$pool->dex->value}->taxSell * 100) : $pool->tax_sell;
                $pool->tax_transfer = isset($report->{$pool->dex->value}->taxTransfer) ? ($report->{$pool->dex->value}->taxTransfer * 100) : $pool->tax_transfer;
                $pool->save();

            }

        }
    }
}
