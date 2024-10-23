<?php

namespace App\Services;

use App\Enums\Frame;
use App\Enums\Lock;
use App\Enums\Reaction;
use App\Models\Token;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Imagick;

class TokenReportService
{
    public bool $is_show_warnings = false;
    public bool $is_finished = false;
    public bool $is_for_group = false;

    public function setWarningsEnabled(bool $value = true): self
    {
        $this->is_show_warnings = $value;
        return $this;
    }

    public function setFinished(bool $value = true): self
    {
        $this->is_finished = $value;
        return $this;
    }

    public function setForGroup(bool $value = true): self
    {
        $this->is_for_group = $value;
        return $this;
    }


    public function main(Token $token): array
    {
        $pools = [];
        $links = [];

        $alert = '';
        $alerts = [
            'is_warn_honeypot',
            'is_warn_liquidity',
            'is_warn_rugpull',
            'is_warn_original',
            'is_warn_scam',
        ];

        if ($this->is_finished)
            foreach ($alerts as $str)
                if ($token->$str) {
                    $alert = __("telegram.text.token_scanner.report.alerts.$str");
                    break;
                }

        $is_ton_network = $token->network->slug === 'ton';
        $lp_burned_warning = $this->is_show_warnings && $token->is_warn_burned && $this->is_finished && $is_ton_network;
        $is_revoked_warning = $this->is_show_warnings && $this->is_finished;
        $is_scam = $token->is_warn_honeypot || $token->is_warn_scam;

        foreach ($token->pools as $pool) {

            $burned_percent = number_format($pool->burned_percent ?? 0.0, 2);
            $locked_percent = number_format($pool->locked_percent ?? 0.0, 2);
            $type = Lock::verbose($pool->locked_type ?? Lock::CHECK);
            $dyor = $pool->locked_dyor ? __('telegram.text.token_scanner.report.lp_locked.dyor') : '';
            $unlocks = $pool->unlocks_at ? __('telegram.text.token_scanner.report.lp_locked.unlocks', ['value' => $pool->unlocks_at->translatedFormat('d M Y')]) : '';

            if ($token->is_warn_honeypot || !$is_ton_network) $lp_burned = '';
            else if (!$this->is_finished) $lp_burned = __('telegram.text.token_scanner.report.lp_burned.scan');
            else if ($pool->burned_amount) $lp_burned = __('telegram.text.token_scanner.report.lp_burned.yes', ['value' => $burned_percent]);
            else $lp_burned = __('telegram.text.token_scanner.report.lp_burned.no');

            if ($token->is_warn_honeypot || !$is_ton_network) $lp_locked = '';
            else if (!$this->is_finished) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.scan');
            else if ($pool->burned_percent > 99) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.burned', ['value' => $burned_percent]);
            else if ($pool->locked_type === Lock::CHECK) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.multiple', ['value' => $locked_percent]);
            else if ($pool->locked_amount) $lp_locked = __('telegram.text.token_scanner.report.lp_locked.yes', ['value' => $locked_percent, 'type' => $type, 'unlocks' => $unlocks, 'dyor' => $dyor, 'link' => $pool->locked_type ? Lock::link($pool->locked_type, $pool->address) : null]);
            else $lp_locked = __('telegram.text.token_scanner.report.lp_locked.no');

            if (!$this->is_finished) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.scan');
            else if ($pool->tax_buy === null) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.unknown');
            else if ($pool->tax_buy < 0) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.no');
            else if ($pool->tax_buy > 30) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.danger', ['value' => $pool->tax_buy]);
            else if ($pool->tax_buy > 0) $tax_buy = __('telegram.text.token_scanner.report.tax_buy.warning', ['value' => $pool->tax_buy]);
            else $tax_buy = __('telegram.text.token_scanner.report.tax_buy.ok');

            if (!$this->is_finished) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.scan');
            else if ($pool->tax_sell === null) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.unknown');
            else if ($pool->tax_sell < 0) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.no');
            else if ($pool->tax_sell > 30) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.danger', ['value' => $pool->tax_sell]);
            else if ($pool->tax_sell > 0) $tax_sell = __('telegram.text.token_scanner.report.tax_sell.warning', ['value' => $pool->tax_sell]);
            else $tax_sell = __('telegram.text.token_scanner.report.tax_sell.ok');

            $pools[] = __('telegram.text.token_scanner.report.pool', [
                'link' => $pool->dex->getLink($pool->address),
                'name' => $pool->dex->name,
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

        $image = public_path('img/blank.png');
        if ($is_scam && (!$token->image || Http::get($token->image)->status() !== 200))
            $image = public_path('img/scam.png');
        else if ($is_scam)
            $image = $this->getScamImage($token->address, $token->image);
        else if ($token->image && Http::get($token->image)->status() === 200)
            $image = $token->image;

        return [
            'image' => $image,
            'text' => __('telegram.text.token_scanner.report.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'address' => $token->address,
                'network' => $token->network->name,

                'description_title' => $token->description ? __('telegram.text.token_scanner.report.description_title') : '',
                'description' => $token->description_formatted,

                'supply' => number_format($token->supply),
                'holders_count' => number_format($token->holders_count),
                'pools' => implode('', $pools),
                'alert' => $alert,

                'links_title' => $links ? __('telegram.text.token_scanner.report.links_title') : '',
                'links' => $links ? (implode('', $links) . "\n") : '',

                'is_known_master' => __('telegram.text.token_scanner.report.is_known_master.' . ($token->is_known_master ? 'yes' : ($this->is_finished ? 'no' : 'scan'))),
                'is_known_wallet' => __('telegram.text.token_scanner.report.is_known_wallet.' . ($token->is_known_wallet ? 'yes' : ($this->is_finished ? 'no' : 'scan'))),

                'is_revoked' => __('telegram.text.token_scanner.report.is_revoked.' . ($token->is_revoked ? 'yes' : 'no')),
                'is_revoked_warning' => $is_revoked_warning ? __('telegram.text.token_scanner.report.is_revoked_warning.' . ($token->is_revoked ? 'yes' : 'no')) : '',
                'lp_burned_warning' => $lp_burned_warning ? __('telegram.text.token_scanner.report.lp_burned.warning') : '',

                'likes_count' => $token->reactions()->where('type', Reaction::LIKE)->count(),
                'dislikes_count' => $token->reactions()->where('type', Reaction::DISLIKE)->count(),
                'is_finished' => $this->is_finished && !$this->is_for_group ? __('telegram.text.token_scanner.report.is_finished') : '',
                'watermark' => $this->is_for_group ? __('telegram.text.token_scanner.watermark') : '',
            ]),
        ];
    }

    public function chart(Token $token, Frame $frame, bool $is_show_text): array
    {
        $pools = [];
        foreach ($token->pools as $pool)
            $pools[] = __('telegram.text.token_scanner.chart.pool', [
                'link' => $pool->dex->getLink($pool->address),
                'name' => $pool->dex->name,
                'price' => $pool->price_formatted,
                'created_at' => $pool->created_at->translatedFormat('d M Y H:i'),
                'fdv' => number_format($pool->fdv, 2),
                'reserve' => number_format($pool->reserve, 2),
                'price_change_m5' => number_format($pool->m5_price_change, 2),
                'price_change_h1' => number_format($pool->h1_price_change, 2),
                'price_change_h6' => number_format($pool->h6_price_change, 2),
                'price_change_h24' => number_format($pool->h24_price_change, 2),
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
            'image' => $this->getPriceChartUrl($token, ... $frame->params()),
            'text' => $is_show_text ? __('telegram.text.token_scanner.chart.text', [
                'name' => $token->name,
                'symbol' => $token->symbol,
                'pools' => implode('', $pools),
                'warning' => $this->is_show_warnings ? __('telegram.text.token_scanner.chart.warning') : '',
                'warnings' => $this->is_show_warnings && !$this->is_for_group ? __('telegram.text.token_scanner.chart.warnings') : '',
                'watermark' => $this->is_for_group ? __('telegram.text.token_scanner.watermark') : '',
            ]) : '',
        ];
    }

    public function holders(Token $token): array
    {
        function getHolderText(array $holder, string $explorer): string {
            return __('telegram.text.token_scanner.holders.holder', [
                'address' => $explorer . $holder['address'],
                'label' => $holder['name'] ?? mb_strcut($holder['address'], 0, 5) . '...' . mb_strcut($holder['address'], -5),
                'balance' => number_format($holder['balance'], 2),
                'percent' => number_format($holder['percent'], 2),
            ]);
        }

        $holders = [];
        foreach (array_slice($token->holders?->all() ?? [], 0, 10) as $holder)
            $holders[] = getHolderText($holder, $token->network->explorer);

        $pools = [];
        foreach ($token->pools as $pool) {

            $poolHolders = [];
            foreach ($pool->holders ?? [] as $holder)
                $poolHolders[] = getHolderText($holder, $token->network->explorer);

            if ($pool->holders)
                $pools[] = __('telegram.text.token_scanner.holders.pool', [
                    'name' => $pool->dex->name,
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
                'actual' => count($holders),
                'total' => $token->holders_count,
                'warning' => $this->is_show_warnings ? __('telegram.text.token_scanner.holders.warning') : '',
                'watermark' => $this->is_for_group ? __('telegram.text.token_scanner.watermark') : '',
            ]),
        ];
    }


    private function getPriceChartUrl(Token $token, string $frame, int $aggregate): string
    {
        $path = storage_path("app/public/charts/price/$token->address.png");
        $network = $token->network;
        $pool = $token->pools()->first();

        Process::path(base_path('utils/charts'))->run("python3 price.py $path $network->slug $pool->address $frame $aggregate");
        return $path;
    }

    private function getHoldersChartUrl(Token $token): string
    {
        $path = storage_path("app/public/charts/holders/{$token->address}.png");
        $holders = implode(' ', array_map(fn ($holder) => number_format($holder['percent'], 2, thousands_separator: ''), $token->holders->all()));
        Process::path(base_path('utils/charts'))->run("python3 pie.py $path $holders");
        return $path;
    }

    private function getScamImage(string $address, string $image): string
    {
        $path = "public/scams/$address.webp";
        Storage::put($path, Http::get($image)->body());

        $scam = new Imagick(storage_path("app/$path"));
        $image = new Imagick(public_path('img/scam.png'));

        $path = storage_path("app/public/scams/$address.png");
        $scam->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        $scam->resizeImage($image->getImageWidth() - 100, $image->getImageHeight() - 100, Imagick::FILTER_BESSEL, 0);
        $image->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        $image->setImageArtifact('compose:args', "1,0,-0.5,0.5");
        $image->compositeImage($scam, Imagick::COMPOSITE_OVERLAY, 50, 50);
        $image->writeImage($path);
        return $path;
    }
}
