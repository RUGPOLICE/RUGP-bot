<?php

namespace App\Jobs;

use App\Enums\Target;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class SendPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60 * 60 * 24;

    public function __construct(public Post $post) {}

    public function handle(): void
    {
        try {

            ini_set('max_execution_time', '0');

            $superusers = explode(',', config('nutgram.superusers'));
            $targets = match ($this->post->target) {
                Target::ALL => Account::query()->whereNot('is_blocked')->get()->merge(Chat::query()->whereNot('is_blocked')->get()),
                Target::ACCOUNTS => Account::query()->whereNot('is_blocked')->get(),
                Target::CHATS => Chat::query()->whereNot('is_blocked')->get(),
                Target::TEST => Account::query()->whereNot('is_blocked')->whereIn('telegram_id', $superusers)->get(),
                Target::CHAT => Chat::query()->whereNot('is_blocked')->where('telegram_id', $this->post->target_id)->get(),
            };

            $this->send($targets);

        } catch (\Throwable $e) {

            Log::error($e->getMessage());

        }
    }

    private function send(Collection $sendables): void
    {
        foreach ($sendables as $sendable) {

            if ($sendable instanceof Account) $client = new Nutgram(config('nutgram.token'));
            else if ($sendable instanceof Chat) $client = new Nutgram(config('nutgram.group_token'));
            else continue;

            try {

                $client->sendImagedMessage(
                    text: $this->post->text,
                    buttons: $this->post->markup,
                    options: ['image' => $this->post->image ? storage_path("app/{$this->post->image}") : null],
                    chat_id: $sendable->telegram_id,
                );

            } catch (\Throwable $e) {

                if (str_contains($e->getMessage(), 'bot was blocked') || str_contains($e->getMessage(), 'bot was kicked') || str_contains($e->getMessage(), 'chat not found')) {

                    $sendable->is_blocked = true;
                    $sendable->save();

                } else Log::error($e->getMessage());

            }

            sleep(0.7);

        }
    }
}
