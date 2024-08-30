<?php

namespace App\Enums;

enum Language: string
{
    case RU = 'ru';
    case EN = 'en';

    public static function keys(): array
    {
        return [
            self::RU->value,
            self::EN->value,
        ];
    }

    public static function key(string $lang): self
    {
        return match ($lang) {
            self::RU->value => self::RU,
            self::EN->value => self::EN,
        };
    }

    public static function language(string $key): self|string
    {
        foreach (self::keys() as $language)
            if ($language === $key)
                return $language;
        return config('app.fallback_locale');
    }
}
