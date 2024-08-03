<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class TelegramController extends Controller
{
    public function handle(Nutgram $bot): void
    {
        try {

            $bot->run();

        } catch (\Throwable $e) {

            Log::error($e);

        }
    }
}
