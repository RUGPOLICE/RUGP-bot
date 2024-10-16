<?php

namespace App\Telegram\Handlers;

use App\Enums\RequestSource;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Pool;
use App\Models\Request;
use App\Models\Token;
use SergiX44\Nutgram\Nutgram;

class StatsHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $accountsCount = Account::query()->count();
        $chatsCount = Chat::query()->count();

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

        $bot->asResponse()->sendImagedMessage("<b>App Statistics</b>\n\nUsers: <b>$accountsCount</b>\nGroups: <b>$chatsCount</b>\n\nTokens: <b>$tokensCount</b>\nPools: <b>$poolsCount</b>\nScam: <b>$scamCount</b>\n\n<b>Requests</b>\n<i>last 24 hours</i>\n\nTelegram: <b>$requestsTelegramCount</b>\nAverage load: <i>$requestsTelegramRate rpm</i>\n\nAPI: <b>$requestsApiCount</b>\nAverage load: <i>$requestsApiRate rpm</i>");
    }
}
