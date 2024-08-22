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

    public static function link(self $lock, string $address): string
    {
        return match ($lock) {
            self::RAFFLE => "https://tonraffles.app/lock/$address",
            self::TONINU => "https://app.toninu.tech/locker/$address",
        };
    }
}
