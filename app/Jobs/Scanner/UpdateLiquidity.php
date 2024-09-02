<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Jobs\Middleware\Localized;
use App\Models\Token;
use App\Services\TonApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Process;

class UpdateLiquidity implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(public Token $token, public ?Language $language = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(TonApiService $tonApiService): void
    {
        foreach ($this->token->pools as $pool) {

            $poolHolders = $tonApiService->getJettonHolders($pool->address, 4);
            if ($poolHolders) {

                [$poolHolders, $holdersCount] = $poolHolders;
                if ($holdersCount) {

                    $holderAddresses = implode(',', array_map(fn ($a) => $a['address'], $poolHolders));

                    $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/convert.js $holderAddresses");
                    $holderAddresses = json_decode($result->output())->addresses;

                    $pool->holders = array_map(fn ($a) => [
                        'address' => $holderAddresses->{$a['address']},
                        'balance' => $a['balance'] / 1000000000,
                        'name' => $a['owner']['name'] ?? (!$a['owner']['is_wallet'] ? __('telegram.text.token_scanner.holders.dex_lock_stake') : null),
                        'percent' => $pool->supply ? ($a['balance'] * 100 / $pool->supply) : 0,
                    ], $poolHolders);
                    $pool->save();

                }

            }

        }
    }
}
