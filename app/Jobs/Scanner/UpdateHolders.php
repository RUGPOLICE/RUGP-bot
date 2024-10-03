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

class UpdateHolders implements ShouldQueue
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
        $key = 'UpdateHolders:' . $this->token->address;
        if (Cache::has($key)) return;
        Cache::set($key, 'scanned', 60 * 10);

        $tokenHolders = $this->token->network->service->getJettonHolders($this->token->address, $this->token->supply, $this->token->network->slug);
        if ($tokenHolders) [$this->token->holders, $this->token->holders_count] = $tokenHolders;
    }
}
