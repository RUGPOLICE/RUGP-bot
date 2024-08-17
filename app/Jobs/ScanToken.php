<?php

namespace App\Jobs;

use App\Enums\Dex;
use App\Enums\Lock;
use App\Exceptions\ScanningError;
use App\Models\Pool;
use App\Models\Token;
use App\Services\DexScreenerService;
use App\Services\TonApiService;
use App\Services\TonHubService;
use App\Telegram\Handlers\TokenReportHandler;
use Carbon\Carbon;
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

    public function handle(Nutgram $bot, DexScreenerService $dexScreenerService, TonApiService $tonApiService, TonHubService $tonHubService): void
    {
        try {

            $dex = [];
            foreach ($dexScreenerService->getPoolsByTokenAddress($this->token->address) as $pool) {

                $dex[] = $pool['pool']['dex'];
                $this->token->update($pool['token']);
                Pool::query()->updateOrCreate(['address' => $pool['pool']['address']], $pool['pool'] + ['token_id' => $this->token->id]);

            }

            if (!$this->token->is_scanned) {

                $tokenMetadata = $tonApiService->getJetton($this->token->address);
                if (!$tokenMetadata)
                    throw new ScanningError(
                        message: __('telegram.errors.scan.metadata', ['address' => $this->token->address]),
                        log_message: "Scan Token Metadata: {$this->token->address}",
                    );

                $this->token->name = $tokenMetadata['metadata']['name'];
                $this->token->symbol = $tokenMetadata['metadata']['symbol'];
                $this->token->owner = $tokenMetadata['admin']['address'] ?? null;
                $this->token->image = $tokenMetadata['metadata']['image'] ?? null;
                $this->token->description = $tokenMetadata['metadata']['description'] ?? null;
                $this->token->holders_count = $tokenMetadata['holders_count'];
                $this->token->supply = $tokenMetadata['total_supply'] / 1000000000;

            }

            $dex = implode(',', $this->token->pools()->whereNull('tax_buy')->orWhereNull('tax_sell')->get()->pluck('dex')->map(fn (Dex $dex) => $dex->value)->all());
            if (!$this->token->is_scanned || !$this->token->is_revoked || $dex) {

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

                    $pool->tax_buy = ($report->{$pool->dex->value}->taxBuy ?? null) * 100;
                    $pool->tax_sell = ($report->{$pool->dex->value}->taxSell ?? null) * 100;
                    $pool->tax_transfer = ($report->{$pool->dex->value}->taxTransfer ?? null) * 100;

                }

            }

            $this->token->is_scanned = true;
            $this->token->save();

        } catch (ScanningError $e) {

            $this->sendError($bot, $e->getMessage());
            Log::error($e->getLogMessage());

        }

        $tokenHolders = $tonApiService->getJettonHolders($this->token->address);
        if ($tokenHolders) {

            $holderAddresses = implode(',', array_map(fn ($a) => $a['address'], $tokenHolders));
            $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/convert.js $holderAddresses");
            $holderAddresses = json_decode($result->output())->addresses;

            $this->token->holders = array_map(fn ($a) => [
                'address' => $holderAddresses->{$a['address']},
                'balance' => $a['balance'] / 1000000000,
                'name' => $a['owner']['name'] ?? (!$a['owner']['is_wallet'] ? __('telegram.text.token_scanner.holders.dex_lock_stake') : null),
                'percent' => $this->token->supply ? ($a['balance'] / 1000000000 * 100 / $this->token->supply) : 0,
            ], $tokenHolders);
            $this->token->save();

        }

        foreach ($this->token->pools as $pool) {

            $poolHolders = $tonApiService->getJettonHolders($pool->address);
            if ($poolHolders) {

                $holderAddress = $poolHolders[0]['owner']['address'];
                $poolMetadata = $tonApiService->getJetton($pool->address);

                if ($poolMetadata) {

                    $pool->supply = $poolMetadata['total_supply'] / 1000000000;

                    if ($holderAddress === '0:0000000000000000000000000000000000000000000000000000000000000000') {

                        $pool->burned_amount = $poolHolders[0]['balance'] / 1000000000;
                        $pool->burned_percent = $poolHolders[0]['balance'] / $poolMetadata['total_supply'] * 100;

                    } else {

                        $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/convert.js $holderAddress");
                        $holderAddress = json_decode($result->output())->addresses->{$holderAddress};

                        $lockedAmount = null;
                        $lockedPercent = null;

                        $lockInfo = $tonHubService->getContractData($holderAddress);
                        if ($lockInfo) {

                            $lockedAmount = $lockInfo['locked_amount'] / 1000000000;
                            $lockedPercent = $lockInfo['locked_amount'] / $poolMetadata['total_supply'] * 100;

                            $pool->locked_type = Lock::RAFFLE;
                            $pool->unlocks_at = Carbon::createFromTimestamp($lockInfo['unlocks_at']);

                        }

                        $toninuAmount = array_reduce(
                            array_filter($poolHolders, fn ($item) => ($item['owner']['name'] ?? '') === 'tinu-locker.ton'),
                            fn ($acc, $item) => $acc + $item['balance'],
                            0
                        );

                        if ($toninuAmount) {

                            $lockedAmount = $toninuAmount / 1000000000;
                            $lockedPercent = $toninuAmount / $poolMetadata['total_supply'] * 100;
                            $pool->locked_type = Lock::TONINU;

                        }

                        $isSmallLock = $lockedPercent && $lockedPercent < 100;
                        $hasLargeHolders = boolval(array_filter($poolHolders, fn ($item) => ($item['balance'] / $poolMetadata['total_supply'] * 100) > 5));
                        $isUnlocked = $pool->unlocks_at && $pool->unlocks_at < now();

                        $pool->locked_dyor = $isSmallLock && $hasLargeHolders || $isUnlocked;
                        $pool->locked_amount = $lockedAmount;
                        $pool->locked_percent = $lockedPercent;

                    }

                    $pool->save();

                }

            }

        }

        $this->sendReport($bot);
    }

    private function sendReport(Nutgram $bot): void
    {
        $pendings = $this->token->pendings()->with('account')->get();
        foreach ($pendings as $pending) {

            try {

                sleep(1);
                (new TokenReportHandler)->main($bot, $this->token, $pending->account->telegram_id, $pending->message_id);

            } catch (\Throwable $e) {

                Log::error("Scan Token Bulk: {$e->getMessage()} [{$pending->account->telegram_id}, {$this->token->address}]");

            }

        }

        $this->token->pendings()->delete();
    }

    private function sendError(Nutgram $bot, string $message): void
    {
        $pendings = $this->token->pendings()->with('account')->get();
        foreach ($pendings as $pending) {

            try {

                sleep(1);
                (new TokenReportHandler)->error($bot, $message, $pending->account->telegram_id, $pending->message_id);

            } catch (\Throwable $e) {

                Log::error("Scan Token Bulk: {$e->getMessage()} [{$pending->account->telegram_id}, {$this->token->address}]");

            }

        }

        $this->token->pendings()->delete();
    }
}
