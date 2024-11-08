<?php

namespace App\Telegram\Handlers;

use App\Enums\RequestSource;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Pool;
use App\Models\Request;
use App\Models\Token;
use App\Services\OpenAiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;

class StatsHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $accountsCount = Account::query()->count();
        $chatsCount = Chat::query()->count();

        $activeAccountsCount = Account::query()->where('last_active_at', '>=', now()->subDays(2))->count();
        $activeChatsCount = Chat::query()->where('last_active_at', '>=', now()->subDays(2))->count();

        $requestsTelegramCount = Request::query()->where('source', RequestSource::TELEGRAM)->where('created_at', '>=', now()->subDay())->count();
        $requestsApiCount = Request::query()->where('source', RequestSource::API)->where('created_at', '>=', now()->subDay())->count();

        $period = now()->startOfMinute()->subDay()->toPeriod(now()->startOfMinute(), 1, 'minutes')->toArray();

        $requestsTelegramRate = Request::query()
            ->selectRaw('COUNT(*) as requests_count, FROM_UNIXTIME(UNIX_TIMESTAMP(created_at) DIV 60 * 60) as time')
            ->where('source', RequestSource::TELEGRAM)
            ->where('created_at', '>=', now()->subDay())
            ->groupByRaw('time')
            ->get()
            ->mapWithKeys(fn ($request) => [$request->time => $request->requests_count])
            ->toArray();

        $requestsTelegramRate = collect($period)->map(fn ($minute) => $requestsTelegramRate[$minute->format('Y-m-d H:i:s')] ?? 0)->average();
        $requestsTelegramRate = number_format($requestsTelegramRate, decimals: 3);

        $requestsApiRate = Request::query()
            ->selectRaw('COUNT(*) as requests_count, FROM_UNIXTIME(UNIX_TIMESTAMP(created_at) DIV 60 * 60) as time')
            ->where('source', RequestSource::API)
            ->where('created_at', '>=', now()->subDay())
            ->groupByRaw('time')
            ->get()
            ->mapWithKeys(fn ($request) => [$request->time => $request->requests_count])
            ->toArray();

        $requestsApiRate = collect($period)->map(fn ($minute) => $requestsApiRate[$minute->format('Y-m-d H:i:s')] ?? 0)->average();
        $requestsApiRate = number_format($requestsApiRate, decimals: 3);

        $tokensCount = Token::query()->count();
        $poolsCount = Pool::query()->count();
        $scamCount = Token::query()
            ->where('is_warn_honeypot', true)
            ->orWhere('is_warn_rugpull', true)
            ->orWhere('is_warn_scam', true)
            ->orWhere('is_warn_liquidity', true)
            ->count();

        $apiStatus = Cache::get('api-status', json_encode(['timestamp' => now()->timestamp, 'old' => ['status' => 200, 'response' => true], 'new' => ['status' => 200, 'response' => true]]));
        $apiStatus = json_decode($apiStatus, true);

        $apiUrl = str_replace('https://', '', config('app.url'));
        $apiStatusTime = Carbon::createFromTimestamp($apiStatus['timestamp'] ?? now()->timestamp)->diffForHumans();
        $oldApiStatusCode = $apiStatus['old']['status'];
        $oldApiResponse = json_encode($apiStatus['old']['response']);
        $newApiStatusCode = $apiStatus['new']['status'];
        $newApiResponse = json_encode($apiStatus['new']['response']);

        $openaiService = app(OpenAiService::class);
        $openaiStatus = $openaiService->isOpenaiAvailable() ? 'Available' : 'Unavailable';

        $bot->asResponse()->sendImagedMessage("<b>App Statistics</b>\n\nUsers: <b>$accountsCount</b>\nActive Users (48h): <b>$activeAccountsCount</b>\n\nGroups: <b>$chatsCount</b>\nActive Groups (48h): <b>$activeChatsCount</b>\n\nTokens: <b>$tokensCount</b>\nPools: <b>$poolsCount</b>\nScam: <b>$scamCount</b>\n\n<b>Requests</b>\n<i>last 24 hours</i>\n\nTelegram: <b>$requestsTelegramCount</b>\nAverage load: <i>$requestsTelegramRate rpm</i>\n\nAPI: <b>$requestsApiCount</b>\nAverage load: <i>$requestsApiRate rpm</i>\n\n<b>rugp.app ($apiStatusTime)</b>\nStatus Code: <i>$oldApiStatusCode</i>\nResponse: <i>$oldApiResponse</i>\n\n<b>$apiUrl ($apiStatusTime)</b>\nStatus Code: <i>$newApiStatusCode</i>\nResponse: <i>$newApiResponse</i>\n\n<b>Open AI</b>\nStatus: <i>$openaiStatus</i>");
    }
}
