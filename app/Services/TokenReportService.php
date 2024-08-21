<?php

namespace App\Services;

use App\Enums\Dex;
use App\Enums\Lock;
use App\Enums\Reaction;
use App\Models\Token;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;

class TokenReportService
{
    public function main(Token $token): array
    {
        $pools = [];
        $links = [];

        foreach ($token->pools as $pool) {

            $burned_percent = number_format($pool->burned_percent ?? 0.0, 2);
            $locked_percent = number_format($pool->locked_percent ?? 0.0, 2);
            $type = Lock::verbose($pool->locked_type ?? Lock::RAFFLE);
            $dyor = $pool->locked_dyor ? '/ more locks! DYOR' : '';
            $unlocks = $pool->unlocks_at ? "(до {$pool->unlocks_at->format('d M Y')})" : '';

            if ($pool->tax_buy === null) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.unknown');
            else if ($pool->tax_buy < 0) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.no');
            else if ($pool->tax_buy > 30) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.danger', ['value' => $pool->tax_buy]);
            else if ($pool->tax_buy > 0) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.warning', ['value' => $pool->tax_buy]);
            else $tax_buy = __('telegram.text.token_scanner.report.tax_buy.ok');

            if ($pool->tax_sell === null) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.unknown');
            else if ($pool->tax_sell < 0) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.no');
            else if ($pool->tax_sell > 30) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.danger', ['value' => $pool->tax_sell]);
            else if ($pool->tax_sell > 0) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.warning', ['value' => $pool->tax_sell]);
            else $tax_sell = __('telegram.text.token_scanner.report.tax_sell.ok');

            $pools[] = __('telegram.text.token_scanner.report.pool', [
                'link' => Dex::link($pool->dex, $pool->address),
                'name' => Dex::verbose($pool->dex),
                'price' => $pool->price_formatted,
                'lp_burned' => __('telegram.text.token_scanner.report.lp_burned.' . ($pool->burned_amount ? 'yes' : 'no'), ['value' => $burned_percent]),
                'lp_locked' => __('telegram.text.token_scanner.report.lp_locked.' . ($pool->locked_amount ? 'yes' : 'no'), ['value' => $locked_percent, 'type' => $type, 'unlocks' => $unlocks, 'dyor' => $dyor]),
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
                'description' => $token->description,
                'supply' => number_format($token->supply ?? 0),
                'holders_count' => number_format($token->holders_count ?? 0),
                'pools' => implode('', $pools),
                'links' => implode('', $links),
                'is_known_master' => __('telegram.text.token_scanner.report.is_known_master.' . ($token->is_known_master ? 'yes' : 'no')),
                'is_known_wallet' => __('telegram.text.token_scanner.report.is_known_wallet.' . ($token->is_known_wallet ? 'yes' : 'no')),
                'is_revoked' => __('telegram.text.token_scanner.report.is_revoked.' . ($token->is_revoked ? 'yes' : 'no')),
                'likes_count' => $token->reactions()->where('type', Reaction::LIKE)->count(),
                'dislikes_count' => $token->reactions()->where('type', Reaction::DISLIKE)->count(),
            ]),
        ];
    }

    public function chart(Token $token): array
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

    public function holders(Token $token): array
    {
        $holders = [];
        foreach (array_slice($token->holders->all(), 0, 10) as $holder)
            $holders[] = __('telegram.text.token_scanner.holders.holder', [
                'address' => $holder['address'],
                'label' => $holder['name'] ?? mb_strcut($holder['address'], 0, 5) . '...' . mb_strcut($holder['address'], -5),
                'balance' => number_format($holder['balance'], 2),
                'percent' => number_format($holder['percent'], 2),
            ]);

        return [
            'image' => $this->getHoldersChartUrl($token),
            'text' => __('telegram.text.token_scanner.holders.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'holders' => implode('', $holders),
            ]),
        ];
    }

    public function volume(Token $token): array
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
            ]);

        return [
            'image' => $this->getVolumeChartUrl($token),
            'text' => __('telegram.text.token_scanner.volume.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'pools' => implode('', $pools),
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

        foreach ($token->pools as $pool) {

            $price = $geckoService->getOhlcv($pool->address);
            $prices[] = implode(':', [
                Dex::verbose($pool->dex),
                implode(',', array_map(fn ($item) => Carbon::createFromTimestamp($item['timestamp'])->format('d.m.Y'), $price)),
                implode(',', array_map(fn ($item) => $item['close'], $price)),
            ]);

        }

        $prices = implode(' ', $prices);
        Process::path(base_path('utils/charts'))->run("python3 line.py $path $prices");
        return $path;
    }

    private function getVolumeChartUrl(Token $token): string
    {
        $path = storage_path("app/public/charts/volume/{$token->address}.png");
        $geckoService = app(GeckoTerminalService::class);
        $prices = [];

        foreach ($token->pools as $pool) {

            $price = $geckoService->getOhlcv($pool->address);
            $prices[] = implode(':', [
                Dex::verbose($pool->dex),
                implode(',', array_map(fn ($item) => Carbon::createFromTimestamp($item['timestamp'])->format('d.m.Y'), $price)),
                implode(',', array_map(fn ($item) => $item['volume'], $price)),
            ]);

        }

        $prices = implode(' ', $prices);
        Process::path(base_path('utils/charts'))->run("python3 bar.py $path $prices");
        return $path;
    }
}
