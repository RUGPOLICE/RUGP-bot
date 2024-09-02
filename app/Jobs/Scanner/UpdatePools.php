<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Jobs\Middleware\Localized;
use App\Models\Pool;
use App\Models\Token;
use App\Services\GeckoTerminalService;
use App\Services\TonApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class UpdatePools implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public function __construct(public Token $token, public ?Language $language = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(GeckoTerminalService $geckoTerminalService, TonApiService $tonApiService): void
    {
        $tokenMetadata = $geckoTerminalService->getTokenInfo($this->token->address);
        if (!$tokenMetadata) throw new MetadataError($this->token);

        $this->token->update($tokenMetadata);
        foreach ($geckoTerminalService->getPoolsByTokenAddress($this->token->address) as $pool) {

            $poolMetadata = $tonApiService->getJetton($pool['address']);
            if (!$poolMetadata) throw new MetadataError($this->token);

            Pool::query()->updateOrCreate(
                ['address' => $pool['address']],
                $pool + ['token_id' => $this->token->id, 'supply' => $poolMetadata['total_supply']]
            );

        }
    }
}
