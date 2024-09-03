<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Jobs\Middleware\Localized;
use App\Models\Token;
use App\Services\GeckoTerminalService;
use App\Services\TonApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class UpdateMetadata implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public function __construct(public Token $token, public ?Language $language = null) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new Localized];
    }

    public function handle(TonApiService $tonApiService, GeckoTerminalService $geckoTerminalService): void
    {
        if (!$this->token->is_scanned) {

            $tokenMetadata = $tonApiService->getJetton($this->token->address);
            if (!$tokenMetadata) throw new MetadataError($this->token);

            $this->token->name = $tokenMetadata['metadata']['name'];
            $this->token->symbol = $tokenMetadata['metadata']['symbol'];
            $this->token->owner = $tokenMetadata['admin']['address'] ?? null;
            $this->token->image = $tokenMetadata['metadata']['image'] ?? null;
            $this->token->description = $tokenMetadata['metadata']['description'] ?? null;
            $this->token->holders_count = $tokenMetadata['holders_count'];
            $this->token->supply = $tokenMetadata['total_supply'];
            $this->token->is_warn_original = $tokenMetadata['verification'] === 'whitelist';
            $this->token->is_scanned = true;
            $this->token->save();

        }

        if (!$this->token->scanned_at || $this->token->scanned_at <= now()->subDay())
            $this->token->update($geckoTerminalService->getToken($this->token->address) + ['scanned_at' => now()]);
    }
}
