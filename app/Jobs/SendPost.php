<?php

namespace App\Jobs;

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

            $this->send(Account::query()->whereNot('is_blocked')->get(), 'telegram_id');
            // $this->send(Chat::query()->whereNot('is_blocked')->get(), 'chat_id');

        } catch (\Throwable $e) {

            Log::error($e->getMessage());

        }
    }

    private function send(Collection $chats, string $property): void
    {
        $superusers = explode(',', config('nutgram.superusers'));
        foreach ($chats as $chat) {

            if ($chat instanceof Account) $client = new Nutgram(config('nutgram.token'));
            else if ($chat instanceof Chat) $client = new Nutgram(config('nutgram.group_token'));
            else continue;

            try {

                if ($this->post->is_test && !in_array(strval($chat->$property), $superusers))
                    continue;

                $client->sendImagedMessage(
                    text: $this->post->text,
                    buttons: $this->post->markup,
                    options: ['image' => $this->post->image ? storage_path("app/{$this->post->image}") : null],
                    chat_id: $chat->$property,
                );

            } catch (\Throwable $e) {

                if (str_contains($e->getMessage(), 'bot was blocked') || str_contains($e->getMessage(), 'bot was kicked') || str_contains($e->getMessage(), 'chat not found')) {

                    $chat->is_blocked = true;
                    $chat->save();

                } else Log::error($e->getMessage());

            }

            sleep(0.7);

        }
    }
}
