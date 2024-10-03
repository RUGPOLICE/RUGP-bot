<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Jobs\Middleware\Localized;
use App\Models\Pool;
use App\Models\Token;
use App\Services\GeckoTerminalService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Cache;

class UpdatePools implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public function __construct(public Token $token, public ?Language $language = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(GeckoTerminalService $geckoTerminalService): void
    {
        $tokenMetadata = $geckoTerminalService->getTokenInfo($this->token->address, $this->token->network->slug);
        if (!$tokenMetadata) throw new MetadataError($this->token);

        $this->token->update($tokenMetadata);
        foreach ($geckoTerminalService->getPoolsByTokenAddress($this->token->address, $this->token->network->slug) as $pool) {

            $key = 'UpdatePools:' . $pool['address'];
            if (Cache::has($key)) continue;
            Cache::set($key, 'scanned', 60 * 10);

            $poolMetadata = $this->token->network->service->getJetton($pool['address'], $this->token->network->slug);
            if (!$poolMetadata) throw new MetadataError($this->token);

            Pool::query()->updateOrCreate(
                ['address' => $pool['address']],
                $pool + ['token_id' => $this->token->id, 'supply' => $poolMetadata['supply']]
            );

        }
    }
}
