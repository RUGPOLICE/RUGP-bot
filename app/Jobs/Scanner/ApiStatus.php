<?php

namespace App\Jobs\Scanner;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SergiX44\Nutgram\Nutgram;

class ApiStatus implements ShouldQueue
{
    use Queueable;

    public function handle(Nutgram $bot): void
    {
        $token = config('app.token');
        $tokenOld = env('APP_TOKEN_OLD', $token);

        $url = config('app.url');
        $network = 'ton';
        $address = 'EQAXUTLNVMa_Hbm_GX2NzxvtoOA_iJU2d5Tf0E715MY_RUGP';

        $responseNew = Http::withHeader('Authorization', "Bearer $token")
            ->get("$url/v1/$network/token/$address");

        $responseOld = Http::withHeader('Authorization', "Bearer $tokenOld")
            ->get("https://rugp.app/api/v1/$network/token/$address");

        Cache::set('api-status', json_encode([
            'timestamp' => now()->timestamp,
            'old' => ['status' => $responseOld->status(), 'response' => $responseOld->status() === 200 ? $responseOld->json()['success'] : null],
            'new' => ['status' => $responseNew->status(), 'response' => $responseNew->status() === 200 ? $responseNew->json()['success'] : null],
        ]), 60 * 5);

        if (App::environment('production')) {

            if ($responseOld->status() !== 200 || $responseOld->json()['success'] !== true)
                foreach (explode(',', config('nutgram.superusers')) as $chat_id)
                    $bot->sendImagedMessage("<b>rugp.app</b> is down! Check the /stats", chat_id: $chat_id);

            if ($responseOld->status() !== 200 || $responseOld->json()['success'] !== true)
                foreach (explode(',', config('nutgram.superusers')) as $chat_id)
                    $bot->sendImagedMessage("<b>$url</b> is down! Check the /stats", chat_id: $chat_id);

        }

    }
}
