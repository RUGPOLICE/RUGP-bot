<?php

namespace App\Jobs\Scanner;

use App\Enums\Lock;
use App\Jobs\Middleware\Localized;
use App\Models\Account;
use App\Models\Token;
use App\Services\TonApiService;
use App\Services\TonHubService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Process;

class CheckBurnLock implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(public Token $token, public ?Account $account = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(TonApiService $tonApiService, TonHubService $tonHubService): void
    {
        foreach ($this->token->pools as $pool) {

            $poolHolders = $tonApiService->getJettonHolders($pool->address);
            if ($poolHolders) {

                [$poolHolders, $holdersCount] = $poolHolders;
                if ($holdersCount) {

                    $holderAddress = $poolHolders[0]['owner']['address'];

                    if ($holderAddress === '0:0000000000000000000000000000000000000000000000000000000000000000') {

                        $pool->burned_amount = $poolHolders[0]['balance'];
                        $pool->burned_percent = $poolHolders[0]['balance'] / $pool->supply * 100;

                    } else {

                        $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/convert.js $holderAddress");
                        $holderAddress = json_decode($result->output())->addresses->{$holderAddress};

                        $lockedAmount = null;
                        $lockedPercent = null;

                        $lockInfo = $tonHubService->getContractData($holderAddress);
                        if ($lockInfo) {

                            $lockedAmount = $lockInfo['locked_amount'];
                            $lockedPercent = $lockInfo['locked_amount'] / $pool->supply * 100;

                            $time = Carbon::createFromTimestamp($lockInfo['unlocks_at']);
                            $pool->locked_type = Lock::RAFFLE;
                            $pool->unlocks_at = $time > now()->addYears(5) ? now()->addYears(5) : $time;

                        }

                        $toninuAmount = array_reduce(
                            array_filter($poolHolders, fn ($item) => ($item['owner']['name'] ?? '') === 'tinu-locker.ton'),
                            fn ($acc, $item) => $acc + $item['balance'],
                            0
                        );

                        if ($toninuAmount) {

                            $lockedAmount = $toninuAmount;
                            $lockedPercent = $toninuAmount / $pool->supply * 100;
                            $pool->locked_type = Lock::TONINU;

                        }

                        $isSmallLock = $lockedPercent && $lockedPercent < 100;
                        $hasLargeHolders = boolval(array_filter($poolHolders, fn ($item) => ($item['balance'] / $pool->supply * 100) > 5));
                        $isUnlocked = $pool->unlocks_at && $pool->unlocks_at < now();

                        $pool->locked_dyor = $isSmallLock && $hasLargeHolders || $isUnlocked;
                        $pool->locked_amount = $lockedAmount;
                        $pool->locked_percent = $lockedPercent;

                    }

                    $pool->save();

                }

            }

        }
    }
}
