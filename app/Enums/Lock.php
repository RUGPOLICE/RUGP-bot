<?php

namespace App\Enums;

enum Lock: int
{
    case RAFFLE = 1;
    case TONINU = 2;

    public static function all(): array
    {
        return [
            self::RAFFLE->value,
            self::TONINU->value,
        ];
    }

    public static function verbose(self $lock): string
    {
        return match ($lock) {
            self::RAFFLE => 'Raffle',
            self::TONINU => 'Ton Inu',
        };
    }
}
