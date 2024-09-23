<?php

namespace App\Enums;

enum RequestModule: string
{
    case SCANNER = 'scanner';

    public static function all(): array
    {
        return [
            self::SCANNER->value,
        ];
    }

    public function verbose(): string
    {
        return match ($this) {
            self::SCANNER => 'Scanner',
        };
    }
}
