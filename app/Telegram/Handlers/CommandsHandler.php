<?php

namespace App\Telegram\Handlers;

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
        $bot->setMyCommands([
            BotCommand::make('start', 'Update the Bot'),
        ], scope: new BotCommandScopeAllPrivateChats(), language_code: 'en');

        $bot->setMyCommands([
            BotCommand::make('start', 'Обновить бота'),
        ], scope: new BotCommandScopeAllPrivateChats(), language_code: 'ru');


        $group = new Nutgram(config('nutgram.group_token'));

        $group->setMyCommands([
            BotCommand::make('scan', 'Get token report'),
            BotCommand::make('p', 'Get token price report'),
            BotCommand::make('v', 'Get token volume report'),
            BotCommand::make('h', 'Get token holders report'),
        ], scope: new BotCommandScopeAllGroupChats(), language_code: 'en');

        $group->setMyCommands([
            BotCommand::make('scan', 'Get token report'),
            BotCommand::make('p', 'Get token price report'),
            BotCommand::make('v', 'Get token volume report'),
            BotCommand::make('h', 'Get token holders report'),
            BotCommand::make('settings', 'Specify bot settings for chat'),
            BotCommand::make('show_warnings', 'Show warnings'),
            BotCommand::make('hide_warnings', 'Hide warnings'),
            BotCommand::make('show_scam_posts', 'Show scam notifications'),
            BotCommand::make('hide_scam_posts', 'Hide scam notifications'),
            ... array_map(fn ($locale) => BotCommand::make('set_' . $locale . '_language', 'Edit language'), \App\Enums\Language::keys()),
        ], scope: new BotCommandScopeAllChatAdministrators(), language_code: 'en');

        $group->setMyCommands([
            BotCommand::make('scan', 'Получить отчет о токене'),
            BotCommand::make('p', 'Получить отчет о цене токена'),
            BotCommand::make('v', 'Получить отчет об объеме токена'),
            BotCommand::make('h', 'Получить отчет о холдерах токена'),
        ], scope: new BotCommandScopeAllGroupChats(), language_code: 'ru');

        $group->setMyCommands([
            BotCommand::make('scan', 'Получить отчет о токене'),
            BotCommand::make('p', 'Получить отчет о цене токена'),
            BotCommand::make('v', 'Получить отчет об объеме токена'),
            BotCommand::make('h', 'Получить отчет о холдерах токена'),
            BotCommand::make('settings', 'Указать настройки для чата'),
            BotCommand::make('show_warnings', 'Показывать предупреждения'),
            BotCommand::make('hide_warnings', 'Скрывать предупреждения'),
            BotCommand::make('show_scam_posts', 'Уведомлять о скам токенах'),
            BotCommand::make('hide_scam_posts', 'Не уведомлять о скам токенах'),
            ... array_map(fn ($locale) => BotCommand::make('set_' . $locale . '_language', 'Установить язык'), \App\Enums\Language::keys()),
        ], scope: new BotCommandScopeAllChatAdministrators(), language_code: 'ru');

        foreach (explode(',', config('nutgram.superusers')) as $superuser)
            $bot->setMyCommands([
                BotCommand::make('start', 'Обновить бота'),
                BotCommand::make('users', 'Посмотреть кол-во пользователей'),
                BotCommand::make('post', 'Опубликовать пост'),
            ], scope: new BotCommandScopeChat($superuser));

        $bot->setMyCommands([
            BotCommand::make('start', 'Обновить бота'),
            BotCommand::make('commands', 'Обновить команды'),
            BotCommand::make('users', 'Посмотреть кол-во пользователей'),
            BotCommand::make('post', 'Опубликовать пост'),
        ], scope: new BotCommandScopeChat(config('nutgram.developers')));

        $bot->sendImagedMessage('Commands have been updated', reply_to_message_id: $bot->messageId());
    }
}
