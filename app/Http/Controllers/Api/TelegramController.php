<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Telegram\Handlers\GroupStartHandler;
use App\Telegram\Handlers\PublicTokenReportHandler;
use App\Telegram\Handlers\SettingsHandler;
use App\Telegram\Middleware\ForAdmins;
use App\Telegram\Middleware\PrivateHandler;
use App\Telegram\Middleware\PublicHandler;
use App\Telegram\Middleware\RetrieveAccount;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;
use SergiX44\Nutgram\Telegram\Properties\ChatMemberStatus;

class TelegramController extends Controller
{
    public function handle(Nutgram $bot): void
    {
        try {

            $bot->run();

        } catch (\Throwable $e) {

            Log::error($e);

        }
    }

    public function group(Nutgram $bot): void
    {
        try {

            $bot = new Nutgram(config('nutgram.group_token'), new Configuration(botName: config('nutgram.group_bot_name')));
            $bot->middleware(RetrieveAccount::class);

            $bot->onCommand('start', GroupStartHandler::class)->middleware(PrivateHandler::class);
            $bot->group(function (Nutgram $bot) {

                $bot->onText('(\$.*|EQ.{46})', [PublicTokenReportHandler::class, 'publicMain']);
                $bot->onCommand('p (\EQ.{46}|.+)', [PublicTokenReportHandler::class, 'publicPrice']);
                $bot->onCommand('v (\EQ.{46}|.+)', [PublicTokenReportHandler::class, 'publicVolume']);
                $bot->onCommand('h (\EQ.{46}|.+)', [PublicTokenReportHandler::class, 'publicHolders']);

                $bot->onMyChatMember(function (Nutgram $bot) {
                    if ($bot->chatMember()->new_chat_member->status == ChatMemberStatus::MEMBER)
                        (new SettingsHandler)($bot);
                });

                $bot->group(function (Nutgram $bot) {

                    $bot->onCommand('settings', SettingsHandler::class);
                    $bot->onCommand('show_warnings', [SettingsHandler::class, 'showWarnings']);
                    $bot->onCommand('hide_warnings', [SettingsHandler::class, 'hideWarnings']);
                    $bot->onCommand('show_scam_posts', [SettingsHandler::class, 'showScamPosts']);
                    $bot->onCommand('hide_scam_posts', [SettingsHandler::class, 'hideScamPosts']);

                    foreach (\App\Enums\Language::keys() as $locale)
                        $bot->onCommand('set_' . $locale . '_language', [SettingsHandler::class, 'set' . ucfirst($locale) . 'Language']);

                })->middleware(ForAdmins::class);

            })->middleware(PublicHandler::class);

            $bot->setRunningMode(Webhook::class);
            $bot->run();

        } catch (\Throwable $e) {

            Log::error($e);

        }
    }
}
