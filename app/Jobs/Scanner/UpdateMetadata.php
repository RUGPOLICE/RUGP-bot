<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\MetadataError;
use App\Jobs\Middleware\Localized;
use App\Models\Token;
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

    public function handle(): void
    {
        if (!$this->token->scanned_at || $this->token->scanned_at <= now()->subDay() || !$this->token->is_revoked) {

            $tokenMetadata = $this->token->network->service->getJetton($this->token->address, $this->token->network->slug);
            if (!$tokenMetadata) throw new MetadataError($this->token);

            $this->token->update($tokenMetadata);
            $this->token->scanned_at = now();
            $this->token->save();

        }
    }
}
