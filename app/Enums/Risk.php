<?php

namespace App\Enums;

enum Risk: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    case DANGER = 4;
    case UNKNOWN = 5;

    public static function verbose(self $risk): string
    {
        return match ($risk) {
            self::LOW => 'БЕЗОПАСНО',
            self::MEDIUM => 'ВНИМАТЕЛЬНО',
            self::HIGH => 'ЕСТЬ РИСК',
            self::DANGER => 'ОПАСНО',
            self::UNKNOWN => 'НЕВОЗМОЖНО ПРОИЗВЕСТИ ВСЕ ПРОВЕРКИ',
        };
    }

    public static function icon(self $risk): string
    {
        return match ($risk) {
            self::LOW => '✔️',
            self::MEDIUM => '⚠️',
            self::HIGH => '❗️',
            self::DANGER => '🛑',
            self::UNKNOWN => '❓',
        };
    }
}
