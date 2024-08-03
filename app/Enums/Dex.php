<?php

namespace App\Enums;

enum Dex: string
{
    case DEDUST = 'dedust';
    case STONFI = 'stonfi';

    public static function all(): array
    {
        return [
            self::DEDUST->value,
            self::STONFI->value,
        ];
    }

    public static function verbose(self $dex): string
    {
        return match ($dex) {
            self::DEDUST => 'DEDUST.IO',
            self::STONFI => 'STON.FI',
        };
    }

    public static function link(self $dex, string $address): string
    {
        return match ($dex) {
            self::DEDUST => "https://dedust.io/pools/$address",
            self::STONFI => "https://app.ston.fi/pools/$address",
        };
    }
}
