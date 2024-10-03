<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Jobs\Middleware\Localized;
use App\Models\Token;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Cache;

class UpdateLiquidity implements ShouldQueue
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
        foreach ($this->token->pools as $pool) {

            $key = 'UpdateLiquidity:' . $pool->address;
            if (Cache::has($key)) continue;
            Cache::set($key, 'scanned', 60 * 10);

            $poolHolders = $this->token->network->service->getJettonHolders($pool->address, $pool->supply, 4, $this->token->network->slug);
            if ($poolHolders) {

                [$poolHolders, $holdersCount] = $poolHolders;
                if ($holdersCount) $pool->update($this->token->network->service->getLock($pool->address, $pool->supply, $poolHolders, $this->token->network->slug));

            }

        }
    }
}
