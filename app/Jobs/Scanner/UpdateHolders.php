<?php

namespace App\Jobs\Scanner;

use App\Jobs\Middleware\Localized;
use App\Models\Account;
use App\Models\Token;
use App\Services\TonApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Process;

class UpdateHolders implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(public Token $token, public ?Account $account = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(TonApiService $tonApiService): void
    {
        $tokenHolders = $tonApiService->getJettonHolders($this->token->address);
        if ($tokenHolders) {

            [$tokenHolders, $this->token->holders_count] = $tokenHolders;
            $holderAddresses = implode(',', array_map(fn ($a) => $a['address'], $tokenHolders));

            if ($this->token->holders_count) {

                $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/convert.js $holderAddresses");
                $holderAddresses = json_decode($result->output())->addresses;

                $this->token->holders = array_map(fn ($a) => [
                    'address' => $holderAddresses->{$a['address']},
                    'balance' => $a['balance'] / 1000000000,
                    'name' => $a['owner']['name'] ?? (!$a['owner']['is_wallet'] ? __('telegram.text.token_scanner.holders.dex_lock_stake') : null),
                    'percent' => $this->token->supply ? ($a['balance'] * 100 / $this->token->supply) : 0,
                ], $tokenHolders);
                $this->token->save();

            }

        }
    }
}
