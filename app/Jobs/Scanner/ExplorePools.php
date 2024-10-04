<?php

namespace App\Jobs\Scanner;

use App\Models\Pool;
use App\Models\Token;
use App\Services\GeckoTerminalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExplorePools implements ShouldQueue
{
    use Queueable;

    public function handle(GeckoTerminalService $service): void
    {
        $delay = now();
        foreach ($service->getNewPools() as $pool) {

            $token = Token::query()->firstOrCreate(['address' => $pool['token']['address']]);
            Pool::query()->updateOrCreate(['address' => $pool['pool']['address']], $pool['pool'] + ['token_id' => $token->id]);
            ScanTokenTon::dispatch($token)->delay($delay = $delay->addSeconds(5));

        }

    }
}
