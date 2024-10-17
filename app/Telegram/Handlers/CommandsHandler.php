<?php

namespace App\Telegram\Handlers;

use App\Enums\Language;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommand;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllChatAdministrators;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllGroupChats;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllPrivateChats;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeChat;

class CommandsHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $oldLocale = App::getLocale();
        foreach (Language::keys() as $locale) {

            App::setLocale($locale);

            $group = new Nutgram(config('nutgram.group_token'));
            $commands = require lang_path("$locale/telegram.php");

            $private = array_map(
                fn ($command, $description) => BotCommand::make($command, $description),
                array_keys($commands['commands']['private']),
                array_values($commands['commands']['private']),
            );

            $public = array_map(
                fn ($command, $description) => BotCommand::make($command, $description),
                array_keys($commands['commands']['public']),
                array_values($commands['commands']['public']),
            );

            $admin = array_map(
                fn ($command, $description) => BotCommand::make($command, $description),
                array_keys($commands['commands']['admin']),
                array_values($commands['commands']['admin']),
            );

            $bot->setMyCommands($private, scope: new BotCommandScopeAllPrivateChats(), language_code: $locale);
            $group->setMyCommands($public, scope: new BotCommandScopeAllGroupChats(), language_code: $locale);
            $group->setMyCommands($admin, scope: new BotCommandScopeAllChatAdministrators(), language_code: $locale);

        }

        App::setLocale(Language::RU->value);

        $commands = require lang_path('ru/telegram.php');
        $private = array_map(
            fn ($command, $description) => BotCommand::make($command, $description),
            array_keys($commands['commands']['private']),
            array_values($commands['commands']['private']),
        );

        foreach (explode(',', config('nutgram.superusers')) as $superuser)
            $bot->setMyCommands([
                ... $private,
                BotCommand::make('stats', 'Посмотреть статистику'),
                BotCommand::make('post', 'Опубликовать пост'),
                BotCommand::make('commands', 'Обновить команды'),
            ], scope: new BotCommandScopeChat($superuser));

        $bot->setMyCommands([
            ... $private,
            BotCommand::make('stats', 'Посмотреть статистику'),
            BotCommand::make('post', 'Опубликовать пост'),
            BotCommand::make('commands', 'Обновить команды'),
        ], scope: new BotCommandScopeChat(config('nutgram.developers')));

        App::setLocale($oldLocale);
        $bot->sendImagedMessage('Commands have been updated', reply_to_message_id: $bot->messageId());
    }
}
