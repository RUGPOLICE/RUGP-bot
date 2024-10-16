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

            $bot->setMyCommands([
                BotCommand::make('start', __('telegram.commands.private.start')),
                BotCommand::make('scan', __('telegram.commands.private.scan')),
            ], scope: new BotCommandScopeAllPrivateChats(), language_code: $locale);

            $group->setMyCommands([
                BotCommand::make('p', __('telegram.commands.public.price')),
                BotCommand::make('h', __('telegram.commands.public.holders')),
            ], scope: new BotCommandScopeAllGroupChats(), language_code: $locale);

            $group->setMyCommands([
                BotCommand::make('p', __('telegram.commands.public.price')),
                BotCommand::make('h', __('telegram.commands.public.holders')),
                BotCommand::make('settings', __('telegram.commands.admin.settings')),
                BotCommand::make('network', __('telegram.commands.admin.network')),
                BotCommand::make('show_warnings', __('telegram.commands.admin.show_warnings')),
                BotCommand::make('hide_warnings', __('telegram.commands.admin.hide_warnings')),
                BotCommand::make('show_scam_posts', __('telegram.commands.admin.show_scam_posts')),
                BotCommand::make('hide_scam_posts', __('telegram.commands.admin.hide_scam_posts')),
                ... array_map(fn ($l) => BotCommand::make("set_{$l}_language", __("telegram.commands.admin.set_{$l}_language")), \App\Enums\Language::keys()),
            ], scope: new BotCommandScopeAllChatAdministrators(), language_code: $locale);

        }

        App::setLocale(Language::RU->value);
        foreach (explode(',', config('nutgram.superusers')) as $superuser)
            $bot->setMyCommands([
                BotCommand::make('start', __('telegram.commands.private.start')),
                BotCommand::make('scan', __('telegram.commands.private.scan')),
                BotCommand::make('stats', 'Посмотреть статистику'),
                BotCommand::make('post', 'Опубликовать пост'),
                BotCommand::make('commands', 'Обновить команды'),
            ], scope: new BotCommandScopeChat($superuser));

        $bot->setMyCommands([
            BotCommand::make('start', __('telegram.commands.private.start')),
            BotCommand::make('scan', __('telegram.commands.private.scan')),
            BotCommand::make('stats', 'Посмотреть статистику'),
            BotCommand::make('post', 'Опубликовать пост'),
            BotCommand::make('commands', 'Обновить команды'),
        ], scope: new BotCommandScopeChat(config('nutgram.developers')));

        App::setLocale($oldLocale);
        $bot->sendImagedMessage('Commands have been updated', reply_to_message_id: $bot->messageId());
    }
}
