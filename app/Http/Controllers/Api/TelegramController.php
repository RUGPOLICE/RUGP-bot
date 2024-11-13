<?php

namespace App\Http\Controllers\Api;

use App\Enums\Frame;
use App\Enums\RequestModule;
use App\Enums\RequestSource;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Network;
use App\Models\Token;
use App\Services\TokenReportService;
use App\Telegram\Handlers\GroupStartHandler;
use App\Telegram\Handlers\PublicTokenReportHandler;
use App\Telegram\Handlers\SettingsHandler;
use App\Telegram\Middleware\ForAdmins;
use App\Telegram\Middleware\PrivateHandler;
use App\Telegram\Middleware\PublicHandler;
use App\Telegram\Middleware\RetrieveAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;
use SergiX44\Nutgram\Telegram\Properties\ChatMemberStatus;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

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

    public function group(): void
    {
        try {

            $commands = require lang_path('ru/telegram.php');
            $admin = array_keys($commands['commands']['admin']);

            $bot = new Nutgram(config('nutgram.group_token'), new Configuration(botName: config('nutgram.group_bot_name')));
            $bot->middleware(RetrieveAccount::class);

            $bot->onCommand('start', GroupStartHandler::class)->middleware(PrivateHandler::class);
            $bot->group(function (Nutgram $bot) use ($admin) {

                $bot->onText('^(\$[a-zA-Z0-9-_\$]*|EQ[a-zA-Z0-9-_]{46}|0x[a-zA-Z0-9-_]{40}|T[a-zA-Z0-9-_]{33}|[a-zA-Z0-9-_]{43}|[a-zA-Z0-9-_]{44}) {explicit_network}$', [PublicTokenReportHandler::class, 'publicMain'])->whereAlpha('explicit_network');
                $bot->onText('^(\$[a-zA-Z0-9-_\$]*|EQ[a-zA-Z0-9-_]{46}|0x[a-zA-Z0-9-_]{40}|T[a-zA-Z0-9-_]{33}|[a-zA-Z0-9-_]{43}|[a-zA-Z0-9-_]{44})$', [PublicTokenReportHandler::class, 'publicMain']);

                $bot->onCommand($admin[0] . ' (\$[a-zA-Z0-9-_\$]*|EQ[a-zA-Z0-9-_]{46}|0x[a-zA-Z0-9-_]{40}|T[a-zA-Z0-9-_]{33}|[a-zA-Z0-9-_]{43}|[a-zA-Z0-9-_]{44}) {explicit_network}$', [PublicTokenReportHandler::class, 'publicPrice'])->whereAlpha('explicit_network');
                $bot->onCommand($admin[0] . ' (\$[a-zA-Z0-9-_\$]*|EQ[a-zA-Z0-9-_]{46}|0x[a-zA-Z0-9-_]{40}|T[a-zA-Z0-9-_]{33}|[a-zA-Z0-9-_]{43}|[a-zA-Z0-9-_]{44})$', [PublicTokenReportHandler::class, 'publicPrice']);

                $bot->onCommand($admin[1] . ' (\$[a-zA-Z0-9-_\$]*|EQ[a-zA-Z0-9-_]{46}|0x[a-zA-Z0-9-_]{40}|T[a-zA-Z0-9-_]{33}|[a-zA-Z0-9-_]{43}|[a-zA-Z0-9-_]{44}) {explicit_network}$', [PublicTokenReportHandler::class, 'publicHolders'])->whereAlpha('explicit_network');
                $bot->onCommand($admin[1] . ' (\$[a-zA-Z0-9-_\$]*|EQ[a-zA-Z0-9-_]{46}|0x[a-zA-Z0-9-_]{40}|T[a-zA-Z0-9-_]{33}|[a-zA-Z0-9-_]{43}|[a-zA-Z0-9-_]{44})$', [PublicTokenReportHandler::class, 'publicHolders']);

                $bot->onMyChatMember(function (Nutgram $bot) {
                    if ($bot->chatMember()->new_chat_member->status == ChatMemberStatus::MEMBER)
                        (new SettingsHandler)($bot);
                });

                $bot->group(function (Nutgram $bot) use ($admin) {

                    $bot->onCommand($admin[2], SettingsHandler::class);
                    $bot->onCallbackQueryData('scanner:settings:warnings', [SettingsHandler::class, 'setWarnings']);
                    $bot->onCallbackQueryData('scanner:settings:scam', [SettingsHandler::class, 'setScam']);
                    $bot->onCallbackQueryData('scanner:settings:network:{network}', [SettingsHandler::class, 'setNetwork']);
                    $bot->onCallbackQueryData('scanner:settings:language:{language}', [SettingsHandler::class, 'setLanguage']);
                    $bot->onCallbackQueryData('scanner:settings:exit', [SettingsHandler::class, 'exit']);

                })->middleware(ForAdmins::class);

            })->middleware(PublicHandler::class);

            $bot->setRunningMode(Webhook::class);
            $bot->run();

        } catch (\Throwable $e) {

            Log::error($e);

        }
    }

    public function chart(Request $request, TokenReportService $tokenReportService, string $network, string $address)
    {
        try {

            $chat_id = $request->get('chat_id');
            if (!$chat_id) return response()->json(['message' => 'chat_id is required'], 400);

            $chat = Chat::query()->where('telegram_id', $chat_id)->first();
            if (!$chat) return response()->json(['message' => 'Chat not found'], 400);

            $command = $request->get('command');
            if (!in_array($command, ['p', 'h'])) return response()->json(['message' => 'command must be p or h'], 400);

            $network = Network::query()->where('slug', $network)->first();
            if (!$network) return response()->json(['message' => 'Currently supported networks: ' . Network::all()->pluck('slug')->implode(', ')], 400);

            $address = Token::getAddress($address, $network);
            if (!$address['success']) return response()->json(['message' => $address['error']], 400);

            $token = Token::query()->firstOrCreate(['address' => $address['address']], ['network_id' => $network->id]);
            $network->job::dispatchSync($token, $chat->language);

            \App\Models\Request::log($request->user(), $token, RequestSource::API, RequestModule::SCANNER);

            $tokenReportService->setWarningsEnabled($chat->is_show_warnings)->setFinished()->setForGroup();
            $params = match ($command) {
                'p' => $tokenReportService->chart($token, Frame::MINUTES, is_show_text: true),
                'h' => $tokenReportService->holders($token),
            };

            $options = [
                'chat_id' => $chat_id,
                'link_preview_options' => LinkPreviewOptions::make(is_disabled: true)
            ];
            if (array_key_exists('image', $params)) $options['image'] = $params['image'];

            $bot = new Nutgram(config('nutgram.group_token'), new Configuration(botName: config('nutgram.group_bot_name')));
            $bot->sendImagedMessage(
                $params['text'],
                options: $options,
            );

        } catch (\Throwable $e) {

            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['message' => 'Server error'], 500);

        }
    }
}
