<?php

namespace App\Jobs;

use App\Enums\Dex;
use App\Exceptions\ScanningError;
use App\Models\Pool;
use App\Models\Token;
use App\Services\DexScreenerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class SimulateToken implements ShouldQueue
{
    use Queueable;

    public function __construct(public Token $token) {}

    public function handle(DexScreenerService $dexScreenerService): void
    {
        try {

            $this->updatePools($dexScreenerService);

            $dex = implode(',', $this->token->pools()->get()->pluck('dex')->map(fn (Dex $dex) => $dex->value)->all());
            if ($dex) $this->simulateTransactions($dex);

            $this->token->save();

        } catch (ScanningError $e) {

            Log::error($e->getLogMessage());

        }
    }

    private function updatePools(DexScreenerService $dexScreenerService): void
    {
        foreach ($dexScreenerService->getPoolsByTokenAddress($this->token->address) as $pool) {

            $this->token->update($pool['token']);
            Pool::query()->updateOrCreate(['address' => $pool['pool']['address']], $pool['pool'] + ['token_id' => $this->token->id]);

        }
    }

    private function simulateTransactions(string $dex): void
    {
        $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/main.js {$this->token->address} $dex");
        $report = json_decode($result->output());

        if (!$report->success)
            throw new ScanningError(
                message: __('telegram.errors.scan.simulator', ['address' => $this->token->address]),
                log_message: "Scan Token Simulator: {$this->token->address}, $report->message, $report->stack",
            );

        $this->token->is_known_master = $report->isKnownMaster;
        $this->token->is_known_wallet = $report->isKnownWallet;

        foreach ($this->token->pools as $pool) {

            $pool->tax_buy = isset($report->{$pool->dex->value}->taxBuy) ? ($report->{$pool->dex->value}->taxBuy * 100) : null;
            $pool->tax_sell = isset($report->{$pool->dex->value}->taxSell) ? ($report->{$pool->dex->value}->taxSell * 100) : null;
            $pool->tax_transfer = isset($report->{$pool->dex->value}->taxTransfer) ? ($report->{$pool->dex->value}->taxTransfer * 100) : null;

        }
    }
}
