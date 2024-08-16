<?php

namespace App\Services;

use App\Enums\Dex;
use App\Enums\Lock;
use App\Models\Token;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class TokenReportService
{
    public function __construct(private array $lines = []) {}


    public function main(Token $token): array
    {
        $supply = number_format($token->supply);
        $holders_count = number_format($token->holders_count);

        $this->addLine("<b>$token->name | $$token->symbol</b>\n\n$token->description");
        $this->addBlank();

        if ($token->supply) $this->addLine("<i>Кол-во: </i><b>$supply</b>");
        if ($token->holders_count) $this->addLine("<i>Холдеры: </i><b>$holders_count</b>");
        $this->addBlank();

        foreach ($token->pools as $pool) {

            $name = Dex::verbose($pool->dex);
            $link = Dex::link($pool->dex, $pool->address);
            $price = number_format($pool->price, 20);
            $price = mb_substr($price, 0, mb_strpos($price, '00000') ?: mb_strlen($price));
            $burned_percent = number_format($pool->burned_percent ?? 0.0, 2);
            $locked_percent = number_format($pool->locked_percent ?? 0.0, 2);

            $this->addLine("<i><b><a href='$link'>$name</a></b></i>:");
            $this->addLine("<i>Цена</i>: <b>$$price</b>");

            if ($pool->burned_amount) $this->addLine("<i>LP сожжены</i>: <b>$burned_percent%</b>");
            else $this->addLine("<i>LP сожжены</i>: <b>0%</b>");

            if ($pool->locked_amount) {

                $type = Lock::verbose($pool->locked_type);
                $dyor = $pool->locked_dyor ? '/ more locks! DYOR' : '';
                $unlocks = $pool->unlocks_at ? "(до {$pool->unlocks_at->format('d M Y')})" : '';
                $this->addLine("<i>LP заблокированы</i>: <b>$locked_percent% on $type $unlocks <i>$dyor</i></b>");

            } else $this->addLine("<i>LP заблокированы</i>: <b>0%</b>");

            if ($pool->tax_buy === null) $this->addLine("<i>Невозможно купить</i>");
            else $this->addLine("<i>Налог на покупку</i>: <b>$pool->tax_buy%</b>");

            if ($pool->tax_sell === null) $this->addLine("<i>Невозможно продать</i>");
            else $this->addLine("<i>Налог на продажу</i>: <b>$pool->tax_sell%</b>");

            $this->addBlank();

        }

        $links = "";
        foreach ($token->websites ?? [] as $website) {
            $label = $website['label'];
            $links .= "<a href='{$website['url']}'><b>$label</b></a> ";
        }

        foreach ($token->socials ?? [] as $social) {
            $label = ucfirst($social['type']);
            $links .= "<a href='{$social['url']}'><b>$label</b></a> ";
        }

        if ($links) {

            $this->addLine($links);
            $this->addBlank();

        }

        $this->addLine($token->riskIcon('master') . ($token->is_known_master ? 'Этот контракт содержит проверенный код' : 'Этот контракт содержит кастомный код'));
        $this->addLine($token->riskIcon('wallet') . ($token->is_known_wallet ? 'Контракт кошелька содержит проверенный код' : 'Контракт кошелька содержит кастомный код'));
        $this->addLine($token->riskIcon('revoked') . ($token->is_revoked ? 'Права отозваны' : 'Права не отозваны'));
        $this->addBlank();

        // $buy_tax = number_format($this->dedust_tax_buy * 100, decimals: 2);
        // $sell_tax = number_format($this->dedust_tax_sell * 100, decimals: 2);
        // $transfer_tax = number_format($this->dedust_tax_transfer * 100, decimals: 2);
        /*if ($pool->dex == Dex::DEDUST) {

            $report[] = $this->riskIcon('dedust_buy') . ($this->dedust_tax_buy < 0 ? 'Невозможно купить' : "Комиссия на покупку: $buy_tax%");
            $report[] = $this->riskIcon('dedust_sell') . ($this->dedust_tax_sell < 0 ? 'Невозможно продать' : "Комиссия на продажу: $sell_tax%");
            $report[] = $this->riskIcon('dedust_transfer') . ($this->dedust_tax_transfer < 0 ? 'Невозможно передать' : "Комиссия на трансфер: $transfer_tax%");

        } elseif ($pool->dex == Dex::STONFI) {

            $report[] = $this->riskIcon('stonfi_deprecated') . ($this->stonfi_deprecated ? 'Права отозваны' : 'Права не отозваны');
            $report[] = $this->riskIcon('stonfi_taxable') . ($this->stonfi_taxable ? 'Присутствует комиссия' : 'Комиссия отсутствует');

        }*/

        return [
            'text' => $this->getReport(),
            'image' => $token->image,
        ];
    }

    public function chart(Token $token): array
    {
        $this->addLine("<b>$token->name | $$token->symbol</b>");
        $this->addBlank();

        $this->addLine("<b>Чарт</b>");
        $this->addBlank();

        foreach ($token->pools as $pool) {

            $name = Dex::verbose($pool->dex);
            $link = Dex::link($pool->dex, $pool->address);

            $price = number_format($pool->price, 20);
            $price = mb_substr($price, 0, mb_strpos($price, '00000') ?: mb_strlen($price));
            $fdv = number_format($pool->fdv, 2);
            $reserve = number_format($pool->reserve, 2);
            $created_at = $pool->created_at->translatedFormat('d M Y H:i');

            $price_change_m5 = number_format($pool->m5_price_change, 2);
            $price_change_h1 = number_format($pool->h1_price_change, 2);
            $price_change_h6 = number_format($pool->h6_price_change, 2);
            $price_change_h24 = number_format($pool->h24_price_change, 2);

            $this->addLine("<a href='$link'>$name</a>");
            $this->addLine("<i>Цена:</i> <b>$$price</b>");
            $this->addLine("<i>FDV:</i> <b>$$fdv</b>");
            $this->addLine("<i>Ликвидность:</i> <b>$$reserve</b>");
            $this->addLine("<i>Изменение цены</i> <i>(5м):</i> <b>$price_change_m5%</b> <i>(1ч):</i> <b>$price_change_h1%</b> <i>(6ч):</i> <b>$price_change_h6%</b> <i>(24ч):</i> <b>$price_change_h24%</b>");
            $this->addLine("<i>Пул создан:</i> <b>$created_at</b>");
            $this->addBlank();

        }

        return [
            'text' => $this->getReport(),
            'image' => $this->getPriceChartUrl($token),
        ];
    }

    public function holders(Token $token): array
    {
        $this->addLine("<b>$token->name | $$token->symbol</b>");
        $this->addBlank();

        $this->addLine("<b>Холдеры</b>");
        $this->addBlank();

        foreach (array_slice($token->holders->all(), 0, 10) as $holder) {

            $address = $holder['name'] ?? mb_strcut($holder['address'], 0, 5) . '...' . mb_strcut($holder['address'], -5);
            $balance = number_format($holder['balance'], 2);
            $percent = number_format($holder['percent'], 2);
            $this->addLine("<a href='tonviewer.com/{$holder['address']}'><i>$address</i></a>: <b>$balance ($percent%)</b>");

        }

        return [
            'text' => $this->getReport(),
            'image' => $this->getHoldersChartUrl($token),
        ];
    }

    public function volume(Token $token): array
    {
        $this->addLine("<b>$token->name | $$token->symbol</b>");
        $this->addBlank();

        $this->addLine("<b>Объем</b>");
        $this->addBlank();

        foreach ($token->pools as $pool) {

            $name = Dex::verbose($pool->dex);
            $link = Dex::link($pool->dex, $pool->address);

            $price = number_format($pool->price, 20);
            $price = mb_substr($price, 0, mb_strpos($price, '00000') ?: mb_strlen($price));
            $created_at = $pool->created_at->translatedFormat('d M Y H:i');

            $volume_m5 = number_format($pool->m5_volume, 2);
            $volume_h1 = number_format($pool->h1_volume, 2);
            $volume_h6 = number_format($pool->h6_volume, 2);
            $volume_h24 = number_format($pool->h24_volume, 2);

            $buys_m5 = number_format($pool->m5_buys);
            $buys_h1 = number_format($pool->h1_buys);
            $buys_h6 = number_format($pool->h6_buys);
            $buys_h24 = number_format($pool->h24_buys);

            $sells_m5 = number_format($pool->m5_sells);
            $sells_h1 = number_format($pool->h1_sells);
            $sells_h6 = number_format($pool->h6_sells);
            $sells_h24 = number_format($pool->h24_sells);

            $this->addLine("<a href='$link'>$name</a>");
            $this->addLine("<i>Цена:</i> <b>$$price</b>");
            $this->addLine("<i>Объем</i> <i>(5м): </i> <b>$$volume_m5</b> <i>(1ч): </i> <b>$$volume_h1</b> <i>(6ч): </i> <b>$$volume_h6</b> <i>(24ч): </i> <b>$$volume_h24</b>");
            $this->addLine("<i>Покупки</i> <i>(5м): </i> <b>$buys_m5</b> <i>(1ч): </i> <b>$buys_h1</b> <i>(6ч): </i> <b>$buys_h6</b> <i>(24ч): </i> <b>$buys_h24</b>");
            $this->addLine("<i>Продажи</i> <i>(5м): </i> <b>$sells_m5</b> <i>(1ч): </i> <b>$sells_h1</b> <i>(6ч): </i> <b>$sells_h6</b> <i>(24ч): </i> <b>$sells_h24</b>");
            $this->addLine("<i>Пул создан:</i> <b>$created_at</b>");
            $this->addBlank();

        }

        return [
            'text' => $this->getReport(),
            'image' => $this->getVolumeChartUrl($token),
        ];
    }


    private function addLine(string $message): void
    {
        $this->lines[] = $message;
    }

    private function addBlank(): void
    {
        $this->lines[] = '';
    }


    private function getReport(): string
    {
        return implode("\n", $this->lines);
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
        Log::error("python3 utils/charts/bar.py $path $prices");
        return $path;
    }
}
