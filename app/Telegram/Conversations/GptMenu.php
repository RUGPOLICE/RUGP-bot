<?php

namespace App\Telegram\Conversations;

use App\Models\Token;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatAction;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

class GptMenu extends InlineMenu
{
    const MAX_ATTEMPTS = 20;

    protected function doOpen(string $text, InlineKeyboardMarkup $buttons, array $opt): Message|null
    {
        return $this->bot->asResponse()->sendImagedMessage($text, $buttons, $opt);
    }

    protected function doUpdate(string $text, ?int $chatId, ?int $messageId, InlineKeyboardMarkup $buttons, array $opt): bool|Message|null
    {
        return $this->bot->asResponse()->sendImagedMessage($text, $buttons, $opt);
    }

    public function start(Nutgram $bot): void
    {
        $this
            ->clearButtons()
            ->menuText(__('gpt.main', ['requests_remaining' => $this->getRemainingAttempts($bot)]))
            ->addButtonRow(InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'back@menu'))
            ->orNext('handle')
            ->showMenu();
    }

    public function handle(Nutgram $bot): void
    {
        if ($this->getRemainingAttempts($bot)) {

            $bot->sendChatAction(ChatAction::TYPING);
            $this->incrementAttempt($bot);
            $openAiService = app(OpenAiService::class);

            $ticker = [];
            $matches = [];
            preg_match('/\$([a-zA-Z0-9-_$]*)/', $bot->message()->text, $matches);

            if (count($matches) >= 2 && ($token = Token::query()->where('symbol', $matches[1])->first())) {

                $pool = $token->pools()->first();
                $links = [];

                foreach ($token->websites ?? [] as $website)
                    $links[] = "{$website['label']} - {$website['url']}";

                foreach ($token->socials ?? [] as $social)
                    $links[] = "{$social['type']} - {$social['url']}";

                $ticker = [
                    'ticker' => $token->symbol,
                    'name' => $token->name,
                    'description' => $token->description,
                    'socials' => implode("\n", $links),
                    'pool_link' => $pool->dex->getLink($pool->address),
                    'swap_link' => match ($pool->dex->slug) {
                        'dedust' => "https://dedust.io/swap/TON/$token->address",
                        'stonfi', 'stonfi-v2' => "https://app.ston.fi/swap?chartVisible=false&ft=TON&tt=$token->symbol",
                    },
                ];

            }

            $account = $bot->get('account');
            $completion = $openAiService->getChatCompletion(
                $bot->message()->text,
                $account->telegram_name ?? $account->telegram_username ?? 'пользователь телеграм',
                $ticker,
            );

            $this
                ->clearButtons()
                ->menuText($completion . __('gpt.remaining', ['requests_remaining' => $this->getRemainingAttempts($bot)]), ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true), 'parse_mode' => ParseMode::HTML])
                ->orNext('handle')
                ->showMenu();

        } else {

            $this
                ->clearButtons()
                ->menuText(__('gpt.limit'))
                ->addButtonRow(InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'back@menu'))
                ->showMenu();

        }
    }

    public function menu(Nutgram $bot): void
    {
        $this->end();
        match ($bot->callbackQuery()->data) {
            'back' => HomeMenu::begin($bot),
        };
    }

    private function getCacheKey(Nutgram $bot): string
    {
        return "gpt:user:{$bot->get('account')->id}";
    }

    private function getRemainingAttempts(Nutgram $bot): int
    {
        Cache::add($this->getCacheKey($bot), 0, now()->endOfDay()->subHours(3));
        return self::MAX_ATTEMPTS - Cache::get($this->getCacheKey($bot));
    }

    private function incrementAttempt(Nutgram $bot): void
    {
        Cache::increment($this->getCacheKey($bot));
    }
}
