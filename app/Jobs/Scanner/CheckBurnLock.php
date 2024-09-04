<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Enums\Lock;
use App\Jobs\Middleware\Localized;
use App\Models\Token;
use App\Services\TonApiService;
use App\Services\TonHubService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class CheckBurnLock implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(public Token $token, public ?Language $language = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(TonApiService $tonApiService, TonHubService $tonHubService): void
    {
        foreach ($this->token->pools as $pool) {

            $key = 'UpdatePools:' . $pool->address;
            if (Cache::has($key)) continue;
            Cache::set($key, 'scanned', 60 * 10);

            $poolHolders = $tonApiService->getJettonHolders($pool->address);
            if ($poolHolders) {



            }

        }
    }
}
