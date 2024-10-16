<?php

namespace App\Enums;

enum Frame: string
{
    case MINUTE = '1m';
    case MINUTES = '15m';
    case HOURS = '4h';
    case DAY = '1d';

    public function params(): array
    {
        return match ($this) {
            self::MINUTE => ['minute', 1],
            self::MINUTES => ['minute', 15],
            self::HOURS => ['hour', 4],
            self::DAY => ['day', 1],
        };
    }

    public static function keys(): array
    {
        return [
            self::MINUTE->value,
            self::MINUTES->value,
            self::HOURS->value,
            self::DAY->value,
        ];
    }

    public static function key(string $frame): self
    {
        return match ($frame) {
            self::MINUTE->value => self::MINUTE,
            self::MINUTES->value => self::MINUTES,
            self::HOURS->value => self::HOURS,
            self::DAY->value => self::DAY,
        };
    }
}
