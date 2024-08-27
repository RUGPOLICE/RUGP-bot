<?php

namespace App\Services;

use App\Enums\Dex;
use App\Enums\Lock;
use App\Enums\Reaction;
use App\Models\Account;
use App\Models\Token;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;

class TokenReportService
{
    public function main(Token $token, ?Account $account = null, bool $is_finished = true): array
    {
        $pools = [];
        $links = [];

        $honeypot = $token->pools()->where('tax_sell', '<', 0)->exists() && $is_finished;
        $lp_burned_warning = $token->pools()
            ->where(function (Builder $query) {
                $query->whereNull('burned_percent');
                $query->orWhere('burned_percent', '<', 99);
                $query->orWhere(function (Builder $query) {
                    $query->where('burned_percent', '<', 99);
                    $query->whereNull('locked_percent');
                    $query->orWhere(function (Builder $query) {
                        $query->where('locked_percent', '<', 99);
                        $query->where('unlocks_at', '>', now());
                    });
                });
            })
            ->exists() && !$honeypot && $is_finished;

        $holders = $token->holders?->slice(0, 10)->filter(fn ($holder) => str_contains($holder['name'], 'MEXC') || str_contains($holder['name'], 'Bybit') || str_contains($holder['name'], 'OKX'))->count();
        $low_pools = $token->pools()->where('h24_volume', '<', 10000)->exists();
        $rugpull_warning = $low_pools && $holders && !$honeypot && $is_finished;

        foreach ($token->pools as $pool) {

            $burned_percent = number_format($pool->burned_percent ?? 0.0, 2);
            $locked_percent = number_format($pool->locked_percent ?? 0.0, 2);
            $type = Lock::verbose($pool->locked_type ?? Lock::RAFFLE);
            $dyor = $pool->locked_dyor ? __('telegram.text.token_scanner.report.lp_locked.dyor') : '';
            $unlocks = $pool->unlocks_at ? __('telegram.text.token_scanner.report.lp_locked.unlocks', ['value' => $pool->unlocks_at->translatedFormat('d M Y')]) : '';

            if ($honeypot) $lp_burned = '';
            else if (!$is_finished) $lp_burned = __('telegram.text.token_scanner.report.lp_burned.scan');
            else if ($pool->burned_amount === null) $lp_burned = __('telegram.text.token_scanner.report.lp_burned.unknown');
            else if ($pool->burned_amount) $lp_burned = __('telegram.text.token_scanner.report.lp_burned.yes', ['value' => $burned_percent]);
            else $lp_burned = __('telegram.text.token_scanner.report.lp_burned.no');

            if ($honeypot) $lp_locked = '';
            else if (!$is_finished) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.scan');
            else if ($pool->burned_amount === null && $pool->locked_amount === null) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.unknown');
            else if ($pool->burned_percent > 99) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.burned', ['value' => $burned_percent]);
            else if ($pool->locked_amount) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.yes', ['value' => $locked_percent, 'type' => $type, 'unlocks' => $unlocks, 'dyor' => $dyor, 'link' => $pool->locked_type ? Lock::link($pool->locked_type, $pool->address) : null]);
            else $lp_locked = __('telegram.text.token_scanner.report.lp_locked.no');

            if (!$is_finished) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.scan');
            else if ($pool->tax_buy === null) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.unknown');
            else if ($pool->tax_buy < 0) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.no');
            else if ($pool->tax_buy > 30) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.danger', ['value' => $pool->tax_buy]);
            else if ($pool->tax_buy > 0) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.warning', ['value' => $pool->tax_buy]);
            else $tax_buy = __('telegram.text.token_scanner.report.tax_buy.ok');

            if (!$is_finished) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.scan');
            else if ($pool->tax_sell === null) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.unknown');
            else if ($pool->tax_sell < 0) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.no');
            else if ($pool->tax_sell > 30) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.danger', ['value' => $pool->tax_sell]);
            else if ($pool->tax_sell > 0) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.warning', ['value' => $pool->tax_sell]);
            else $tax_sell = __('telegram.text.token_scanner.report.tax_sell.ok');

            $pools[] = __('telegram.text.token_scanner.report.pool', [
                'link' => Dex::link($pool->dex, $pool->address),
                'name' => Dex::verbose($pool->dex),
                'price' => $pool->price_formatted,
                'lp_burned' => $lp_burned,
                'lp_locked' => $lp_locked,
                'tax_buy' => $tax_buy,
                'tax_sell' => $tax_sell,
            ]);

        }

        foreach ($token->websites ?? [] as $website)
            $links[] = __('telegram.text.token_scanner.report.link', ['url' => $website['url'], 'label' => $website['label']]);

        foreach ($token->socials ?? [] as $social)
            $links[] = __('telegram.text.token_scanner.report.link', ['url' => $social['url'], 'label' => $social['type']]);

        return [
            'image' => $token->image,
            'text' => __('telegram.text.token_scanner.report.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'address' => $token->address,

                'description_title' => $token->description ? __('telegram.text.token_scanner.report.description_title') : '',
                'description' => $token->description_formatted,

                'supply' => number_format($token->supply / 1000000000),
                'holders_count' => number_format($token->holders_count),
                'pools' => implode('', $pools),

                'lp_burned_warning' => ($lp_burned_warning && !$account->is_hide_warnings) ? __('telegram.text.token_scanner.report.lp_burned.warning') : '',
                'rugpull_warning' => $rugpull_warning ? __('telegram.text.token_scanner.report.rugpull') : '',

                'links_title' => $links ? __('telegram.text.token_scanner.report.links_title') : '',
                'links' => $links ? (implode('', $links) . "\n") : '',

                'is_known_master' => __('telegram.text.token_scanner.report.is_known_master.' . ($token->is_known_master ? 'yes' : ($is_finished ? 'no' : 'scan'))),
                'is_known_wallet' => __('telegram.text.token_scanner.report.is_known_wallet.' . ($token->is_known_wallet ? 'yes' : ($is_finished ? 'no' : 'scan'))),

                'is_revoked' => __('telegram.text.token_scanner.report.is_revoked.' . ($token->is_revoked ? 'yes' : 'no')),
                'is_revoked_warning' => $account->is_hide_warnings ? '' : __('telegram.text.token_scanner.report.is_revoked_warning.' . ($token->is_revoked ? 'yes' : 'no')),

                'likes_count' => $token->reactions()->where('type', Reaction::LIKE)->count(),
                'dislikes_count' => $token->reactions()->where('type', Reaction::DISLIKE)->count(),
                'is_finished' => $is_finished ? __('telegram.text.token_scanner.report.is_finished') : '',
            ]),
        ];
    }

    public function chart(Token $token, ?Account $account = null): array
    {
        $pools = [];
        foreach ($token->pools as $pool)
            $pools[] = __('telegram.text.token_scanner.chart.pool', [
                'link' => Dex::link($pool->dex, $pool->address),
                'name' => Dex::verbose($pool->dex),
                'price' => $pool->price_formatted,
                'created_at' => $pool->created_at->translatedFormat('d M Y H:i'),
                'fdv' => number_format($pool->fdv, 2),
                'reserve' => number_format($pool->reserve, 2),
                'price_change_m5' => number_format($pool->m5_price_change, 2),
                'price_change_h1' => number_format($pool->h1_price_change, 2),
                'price_change_h6' => number_format($pool->h6_price_change, 2),
                'price_change_h24' => number_format($pool->h24_price_change, 2),
            ]);

        return [
            'image' => $this->getPriceChartUrl($token),
            'text' => __('telegram.text.token_scanner.chart.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'pools' => implode('', $pools),
            ]),
        ];
    }

    public function holders(Token $token, ?Account $account = null): array
    {
        $holders = [];
        foreach (array_slice($token->holders?->all() ?? [], 0, 10) as $holder)
            $holders[] = __('telegram.text.token_scanner.holders.holder', [
                'address' => $holder['address'],
                'label' => $holder['name'] ?? mb_strcut($holder['address'], 0, 5) . '...' . mb_strcut($holder['address'], -5),
                'balance' => number_format($holder['balance'], 2),
                'percent' => number_format($holder['percent'], 2),
            ]);

        $pools = [];
        foreach ($token->pools as $pool) {

            $poolHolders = [];
            foreach ($pool->holders ?? [] as $holder)
                $poolHolders[] = __('telegram.text.token_scanner.holders.holder', [
                    'address' => $holder['address'],
                    'label' => $holder['name'] ?? mb_strcut($holder['address'], 0, 5) . '...' . mb_strcut($holder['address'], -5),
                    'balance' => number_format($holder['balance'], 2),
                    'percent' => number_format($holder['percent'], 2),
                ]);

            if ($pool->holders)
                $pools[] = __('telegram.text.token_scanner.holders.pool', [
                    'name' => Dex::verbose($pool->dex),
                    'address' => $pool->address,
                    'holders' => implode('', $poolHolders),
                ]);

        }

        return [
            'image' => $this->getHoldersChartUrl($token),
            'text' => __('telegram.text.token_scanner.holders.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'holders' => implode('', $holders),
                'pools' => implode('', $pools),
                'warning' => $account->is_hide_warnings ? '' : __('telegram.text.token_scanner.holders.warning'),
            ]),
        ];
    }

    public function volume(Token $token, ?Account $account = null): array
    {
        $pools = [];
        foreach ($token->pools as $pool)
            $pools[] = __('telegram.text.token_scanner.volume.pool', [
                'link' => Dex::link($pool->dex, $pool->address),
                'name' => Dex::verbose($pool->dex),
                'price' => $pool->price_formatted,
                'created_at' => $pool->created_at->translatedFormat('d M Y H:i'),
                'volume_m5' => number_format($pool->m5_volume, 2),
                'volume_h1' => number_format($pool->h1_volume, 2),
                'volume_h6' => number_format($pool->h6_volume, 2),
                'volume_h24' => number_format($pool->h24_volume, 2),
                'buys_m5' => number_format($pool->m5_buys),
                'buys_h1' => number_format($pool->h1_buys),
                'buys_h6' => number_format($pool->h6_buys),
                'buys_h24' => number_format($pool->h24_buys),
                'sells_m5' => number_format($pool->m5_sells),
                'sells_h1' => number_format($pool->h1_sells),
                'sells_h6' => number_format($pool->h6_sells),
                'sells_h24' => number_format($pool->h24_sells),
                'warning' => $account->is_hide_warnings ? '' : __('telegram.text.token_scanner.volume.warning'),
            ]);

        return [
            'image' => $this->getVolumeChartUrl($token),
            'text' => __('telegram.text.token_scanner.volume.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'pools' => implode('', $pools),
                'warning' => $account->is_hide_warnings ? '' : __('telegram.text.token_scanner.volume.warning'),
            ]),
        ];
    }


    private function getHoldersChartUrl(Token $token): string
    {
        $path = storage_path("app/public/charts/holders/{$token->address}.png");
        $holders = implode(' ', array_map(fn ($holder) => number_format($holder['percent'], 2), $token->holders->all()));
        Process::path(base_path('utils/charts'))->run("python3 pie.py $path $holders");
        return $path;
    }

    private function getPriceChartUrl(Token $token): string
    {
        $path = storage_path("app/public/charts/price/{$token->address}.png");
        $geckoService = app(GeckoTerminalService::class);
        $prices = [];

        $is_new = $token->pools->map(fn ($pool) => $pool->created_at >= now()->subWeeks(2))->contains(true);
        foreach ($token->pools as $pool) {

            $price = $geckoService->getOhlcv($pool->address, $is_new);
            $prices[] = implode(':', [
                Dex::verbose($pool->dex),
                implode(',', array_map(fn ($item) => Carbon::createFromTimestamp($item['timestamp'])->format('d.m.Y.H.i'), $price)),
                implode(',', array_map(fn ($item) => $item['close'], $price)),
            ]);

        }

        $prices = implode(' ', $prices);
        $is_new = intval($is_new);

        Process::path(base_path('utils/charts'))->run("python3 line.py $path $is_new $prices");
        return $path;
    }

    private function getVolumeChartUrl(Token $token): string
    {
        $path = storage_path("app/public/charts/volume/{$token->address}.png");
        $geckoService = app(GeckoTerminalService::class);
        $prices = [];

        $is_new = $token->pools->map(fn ($pool) => $pool->created_at >= now()->subWeeks(2))->contains(true);
        foreach ($token->pools as $pool) {

            $price = $geckoService->getOhlcv($pool->address, $is_new);
            $prices[] = implode(':', [
                Dex::verbose($pool->dex),
                implode(',', array_map(fn ($item) => Carbon::createFromTimestamp($item['timestamp'])->format('d.m.Y.H.i'), $price)),
                implode(',', array_map(fn ($item) => $item['volume'], $price)),
            ]);

        }

        $prices = implode(' ', $prices);
        $is_new = intval($is_new);

        Process::path(base_path('utils/charts'))->run("python3 bar.py $path $is_new $prices");
        return $path;
    }
}
