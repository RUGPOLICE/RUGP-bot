<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Jobs\Middleware\Localized;
use App\Models\Pool;
use App\Models\Token;
use App\Services\DexScreenerService;
use App\Services\TonApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class UpdatePools implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public function __construct(public Token $token, public Language $language) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(DexScreenerService $dexScreenerService, TonApiService $tonApiService): void
    {
        foreach ($dexScreenerService->getPoolsByTokenAddress($this->token->address) as $pool) {

            $poolMetadata = $tonApiService->getJetton($pool['pool']['address']);
            if (!$poolMetadata) throw new MetadataError($this->token);

            $this->token->update($pool['token']);
            Pool::query()->updateOrCreate(
                ['address' => $pool['pool']['address']],
                $pool['pool'] + ['token_id' => $this->token->id, 'supply' => $poolMetadata['total_supply']]
            );

        }
    }
}
