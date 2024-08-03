<?php

namespace App\Jobs;

use App\Enums\Dex;
use App\Models\Pool;
use App\Models\Token;
use App\Services\DexScreenerService;
use App\Services\TokenReportService;
use App\Services\TonApiService;
use App\Telegram\Handlers\TokenReportHandler;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use SergiX44\Nutgram\Nutgram;

class ScanToken implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(public Token $token) {}

    public function uniqueId(): string
    {
        return $this->token->address;
    }

    public function handle(Nutgram $bot, DexScreenerService $dexScreenerService, TonApiService $tonApiService, TokenReportService $tokenReportService): void
    {
        $dex = [];
        foreach ($dexScreenerService->getPoolsByTokenAddress($this->token->address) as $pool) {

            $dex[] = $pool['pool']['dex'];
            $this->token->update($pool['token']);
            Pool::query()->updateOrCreate(['address' => $pool['pool']['address']], $pool['pool'] + ['token_id' => $this->token->id]);

        }

        if (!$this->token->is_scanned) {

            $dex = implode(',', $dex ?: Dex::all());
            $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/main.js {$this->token->address} $dex");
            $report = json_decode($result->output());

            if ($report->success) {

                $this->token->name = $report->name;
                $this->token->symbol = $report->symbol;

                $this->token->is_known_master = $report->isKnownMaster;
                $this->token->is_known_wallet = $report->isKnownWallet;

                // $this->token->dedust_tax_buy = $report->dedust->taxBuy ?? null;
                // $this->token->dedust_tax_sell = $report->dedust->taxSell ?? null;
                // $this->token->dedust_tax_transfer = $report->dedust->taxTransfer ?? null;
                // $this->token->stonfi_deprecated = $report->stonfi->deprecated ?? null;
                // $this->token->stonfi_taxable = $report->stonfi->taxable ?? null;

                $this->token->is_scanned = true;
                $this->token->save();

            } else {

                Log::error('Scan Token Honeypot: ' . $report->stack);
                return;

            }

        }

        $tokenMetadata = $tonApiService->getJetton($this->token->address);
        if ($tokenMetadata) {

            $this->token->name = $tokenMetadata['metadata']['name'];
            $this->token->symbol = $tokenMetadata['metadata']['symbol'];
            $this->token->owner = $tokenMetadata['admin']['address'];
            $this->token->image = $tokenMetadata['metadata']['image'];
            $this->token->description = $tokenMetadata['metadata']['description'];
            $this->token->holders_count = $tokenMetadata['holders_count'];
            $this->token->supply = $tokenMetadata['total_supply'];
            $this->token->save();

        }

        $tokenHolders = $tonApiService->getJettonHolders($this->token->address);
        if ($tokenHolders) {

            $this->token->holders = $tokenHolders;
            $this->token->save();

        }

        $pendings = $this->token->pendings()->with('account')->get();
        foreach ($pendings as $pending) {

            try {

                sleep(1);
                (new TokenReportHandler)->main($bot, $this->token, $pending->account->telegram_id, $pending->message_id);

            } catch (\Throwable $e) {

                Log::error('Scan Token Bulk: ' . $e->getMessage());

            }

        }

        $this->token->pendings()->delete();
    }
}
