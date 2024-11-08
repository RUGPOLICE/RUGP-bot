<?php

namespace App\Enums;

enum Lock: int
{
    case RAFFLE = 1;
    case TONINU = 2;
    case CHECK = 3;

    public static function all(): array
    {
        return [
            self::RAFFLE->value,
            self::TONINU->value,
            self::CHECK->value,
        ];
    }

    public static function key(int $value): self
    {
        return match ($value) {
            self::RAFFLE->value => self::RAFFLE,
            self::TONINU->value => self::TONINU,
            self::CHECK->value => self::CHECK,
        };
    }

    public static function verbose(self $lock): string
    {
        return match ($lock) {
            self::RAFFLE => 'Raffle',
            self::TONINU => 'Ton Inu',
            self::CHECK => 'Check LP',
        };
    }

    public static function link(self $lock, string $address): string
    {
        return match ($lock) {
            self::RAFFLE => "https://tonraffles.app/lock/$address",
            self::TONINU => "https://app.toninu.tech/locker/$address",
            self::CHECK => "https://tonviewer.com/$address",
        };
    }
}
