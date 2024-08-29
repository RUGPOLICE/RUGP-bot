<?php

namespace App\Jobs\Scanner;

use App\Jobs\ScanToken;
use App\Models\Pool;
use App\Models\Token;
use App\Services\GeckoTerminalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;

class ExplorePools implements ShouldQueue
{
    use Queueable;

    public function handle(GeckoTerminalService $service): void
    {
        $delay = now();
        foreach ($service->getNewPools() as $pool) {

            $token = Token::query()->firstOrCreate(['address' => $pool['token']['address']]);
            Pool::query()->updateOrCreate(['address' => $pool['pool']['address']], $pool['pool'] + ['token_id' => $token->id]);

            Bus::chain([
                Bus::batch([
                    new UpdateMetadata($token),
                    new UpdatePools($token)
                ]),
                Bus::batch([
                    new SimulateTransactions($token),
                    new UpdateHolders($token),
                    new UpdateLiquidity($token),
                    new CheckBurnLock($token),
                ])->allowFailures(),
            ])->dispatch()->delay($delay = $delay->addSeconds(5));

        }

    }
}
