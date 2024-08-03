<?php

namespace App\Services;

use App\Enums\Dex;
use App\Models\Token;

class TokenReportService
{
    public function main(Token $token): string
    {
        $report = [];
        $supply = number_format($token->supply);
        $holders_count = number_format($token->holders_count);
        $is_revoked = $token->is_revoked ? 'Права отозваны' : 'Права не отозваны';

        $report[] = "<blockquote expandable><b>$token->name | $$token->symbol</b>\n\n$token->description</blockquote>";
        $report[] = "";

        if ($token->supply) $report[] = "<i>Кол-во: </i><b>$supply</b>";
        if ($token->holders_count) $report[] = "<i>Холдеры: </i><b>$holders_count</b>";
        $report[] = "";

        foreach ($token->pools as $pool) {
            $name = Dex::verbose($pool->dex);
            $price = number_format($pool->price, 2);
            $report[] = "<i><b>$name</b></i>: <b>$$price</b>";
        }
        $report[] = "";

        $links = "";
        foreach ($token->websites as $website) {
            $label = $website['label'];
            $links .= "<a href='{$website['url']}'><b>$label</b></a> ";
        }

        foreach ($token->socials as $social) {
            $label = ucfirst($social['type']);
            $links .= "<a href='{$social['url']}'><b>$label</b></a> ";
        }

        $report[] = $links;
        $report[] = "";

        $report[] = $token->riskIcon('master') . ($token->is_known_master ? 'Этот контракт содержит проверенный код' : 'Этот контракт содержит кастомный код');
        $report[] = $token->riskIcon('wallet') . ($token->is_known_wallet ? 'Контракт кошелька содержит проверенный код' : 'Контракт кошелька содержит кастомный код');
        $report[] = $token->riskIcon('revoked') . $is_revoked;
        $report[] = "";

        $report[] = "<i>Honeypot: </i><b>Не проверено</b>";
        $report[] = "<i>Buy Tax: </i><b>Не проверено</b>";
        $report[] = "<i>Sell Tax: </i><b>Не проверено</b>";
        // $report[] = "<i>Lock Liq: </i><b>Не проверено</b>";
        // $report[] = "<i>Burn Liq: </i><b>Не проверено</b>";

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

        return implode("\n", $report);
    }

    public function chart(Token $token): string
    {
        $report = [];
        $report[] = "<b>Чарт</b>";
        $report[] = "";

        foreach ($token->pools as $pool) {

            $name = Dex::verbose($pool->dex);
            $link = Dex::link($pool->dex, $pool->address);

            $price = number_format($pool->price, 2);
            $fdv = number_format($pool->fdv, 2);
            $reserve = number_format($pool->reserve, 2);
            $price_change = number_format($pool->h24_price_change, 2);

            $report[] = "<blockquote><a href='$link'>$name</a>";
            $report[] = "<i>Цена:</i> <b>$$price</b>";
            $report[] = "<i>FDV:</i> <b>$$fdv</b>";
            $report[] = "<i>Ликвидность:</i> <b>$$reserve</b>";
            $report[] = "<i>Изменение цены (24ч):</i> <b>$price_change%</b>";
            $report[] = "<i>Пул создан:</i> <b>{$pool->created_at->translatedFormat('d M Y H:i')}</b></blockquote>";
            $report[] = "";

        }

        // Chart
        return implode("\n", $report);
    }

    public function holders(Token $token): string
    {
        $report = [];
        $report[] = "<b>Холдеры</b>";
        $report[] = "";

        foreach ($token->holders as $holder) {

            $address = mb_strcut($holder['address'], 0, 5) . '...' . mb_strcut($holder['address'], -5);
            $balance = number_format($holder['balance']);
            $report[] = "<a href='tonviewer.com/{$holder['address']}'><i>$address</i></a>: <b>$balance $token->symbol</b>";

        }

        // Chart
        return implode("\n", $report);
    }

    public function volume(Token $token): string
    {
        $report = [];
        $report[] = "<b>Объем</b>";
        $report[] = "";

        foreach ($token->pools as $pool) {

            $name = Dex::verbose($pool->dex);
            $link = Dex::link($pool->dex, $pool->address);

            $price = number_format($pool->price, 2);
            $fdv = number_format($pool->fdv, 2);
            $reserve = number_format($pool->reserve, 2);
            $volume = number_format($pool->h24_volume, 2);
            $buys = number_format($pool->h24_buys);
            $sells = number_format($pool->h24_sells);

            $report[] = "<blockquote><a href='$link'>$name</a>";
            $report[] = "<i>Цена:</i> <b>$$price</b>";
            $report[] = "<i>FDV:</i> <b>$$fdv</b>";
            $report[] = "<i>Ликвидность:</i> <b>$$reserve</b>";
            $report[] = "<i>Объем (24ч):</i> <b>$$volume</b>";
            $report[] = "<i>Покупки (24ч):</i> <b>$buys</b>";
            $report[] = "<i>Продажи (24ч):</i> <b>$sells</b>";
            $report[] = "<i>Пул создан:</i> <b>{$pool->created_at->translatedFormat('d M Y H:i')}</b></blockquote>";
            $report[] = "";

        }

        // Chart
        return implode("\n", $report);
    }
}
