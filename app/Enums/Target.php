<?php

namespace App\Enums;

enum Target: string
{
    case ALL = 'all';
    case ACCOUNTS = 'account';
    case CHATS = 'chats';
    case TEST = 'test';
    case CHAT = 'chat';

    public function verbose(): string
    {
        return match ($this) {
            self::ALL => 'Всем',
            self::ACCOUNTS => 'Юзеры',
            self::CHATS => 'Группы',
            self::CHAT => 'Чат',
            self::TEST => 'Тест',
        };
    }

    public static function keys(): array
    {
        return [
            self::ALL->value,
            self::ACCOUNTS->value,
            self::CHATS->value,
            self::CHAT->value,
            self::TEST->value,
        ];
    }

    public static function key(string $target): self
    {
        return match ($target) {
            self::ALL->value => self::ALL,
            self::ACCOUNTS->value => self::ACCOUNTS,
            self::CHATS->value => self::CHATS,
            self::TEST->value => self::TEST,
            default => self::CHAT,
        };
    }
}
