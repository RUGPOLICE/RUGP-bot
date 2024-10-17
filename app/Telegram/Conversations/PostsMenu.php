<?php

namespace App\Telegram\Conversations;

use App\Enums\Target;
use App\Jobs\SendPost;
use App\Models\Chat;
use App\Models\Post;
use Carbon\Carbon;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

class PostsMenu extends ImagedEditableInlineMenu
{
    public Post $post;

    protected function doOpen(string $text, InlineKeyboardMarkup $buttons, array $opt): Message|null
    {
        return $this->bot->sendImagedMessage($text, $buttons, $opt);
    }

    protected function doUpdate(string $text, ?int $chatId, ?int $messageId, InlineKeyboardMarkup $buttons, array $opt): bool|Message|null
    {
        $this->bot->deleteMessage($this->bot->chatId(), $messageId);
        return $this->bot->sendImagedMessage($text, $buttons, $opt);
    }


    public function start(Nutgram $bot): void
    {
        $this->post = new Post;
        $this
            ->clearButtons()
            ->menuText('Выберите тип публикации или введите ID (или username) чата')
            ->addButtonRow(
                InlineKeyboardButton::make(Target::ALL->verbose(), callback_data: Target::ALL->value . '@target'),
                InlineKeyboardButton::make(Target::TEST->verbose(), callback_data: Target::TEST->value . '@target'),
            )
            ->addButtonRow(
                InlineKeyboardButton::make(Target::ACCOUNTS->verbose(), callback_data: Target::ACCOUNTS->value . '@target'),
                InlineKeyboardButton::make(Target::CHATS->verbose(), callback_data: Target::CHATS->value . '@target'),
                InlineKeyboardButton::make(Target::CHAT->verbose(), callback_data: Target::CHAT->value . '@chat'),
            )
            ->orNext('chat')
            ->showMenu();
    }

    public function chat(Nutgram $bot): void
    {
        if (!Chat::query()->where('telegram_id', $bot->message()->text)->exists()) {

            $bot->sendMessage('Чат не найден. Попробуйте другой');
            return;

        }

        $this->target($bot, $bot->message()->text);
    }

    public function target(Nutgram $bot, ?string $data = null): void
    {
        $this->post->target = Target::key($this->bot->callbackQuery()?->data ?? $data);
        if ($this->post->target === Target::CHAT) $this->post->target_id = $data;

        $this
            ->clearButtons()
            ->menuText('Введите текст, поддержка HTML разметки присутствует')
            ->orNext('text')
            ->showMenu();
    }

    public function text(Nutgram $bot): void
    {
        $text = $bot->message()->text;
        if (!$text) {

            $bot->sendMessage('Введите текст');
            return;

        }

        $this->post->text = $text;
        $this
            ->clearButtons()
            ->menuText('Отправьте картинку')
            ->addButtonRow(InlineKeyboardButton::make('Пропустить', callback_data: 'null@image'))
            ->orNext('image')
            ->showMenu();
    }

    public function image(Nutgram $bot): void
    {
        if ($bot->message()->photo) {

            $count = count($bot->message()->photo);
            $photo = $bot->message()->photo[$count - 1];

            $fileName = "posts/$photo->file_unique_id.jpg";
            $bot->getFile($photo->file_id)->saveToDisk($fileName);

            $this->post->image = $fileName;

        }

        $this->clearButtons()
            ->menuText('Введите кнопки в формате "Текст - ссылка", каждая с новой строки.')
            ->addButtonRow(InlineKeyboardButton::make('Пропустить', callback_data: 'null@buttons'))
            ->orNext('buttons')
            ->showMenu();
    }

    public function buttons(Nutgram $bot): void
    {
        $buttons = $bot->message()->text;
        if ($buttons && !$this->bot->message()?->from?->is_bot)
            $this->post->buttons = $buttons;

        $this->clearButtons()
            ->menuText('Выберите время публикации')
            ->addButtonRow(
                InlineKeyboardButton::make('Мгновенно', callback_data: 'instant@prepare'),
                InlineKeyboardButton::make('Выбрать время', callback_data: 'delay@prepare'),
            )
            ->addButtonRow(InlineKeyboardButton::make('Выход', callback_data: 'none'))
            ->showMenu();
    }

    public function prepare(Nutgram $bot): void
    {
        if ($bot->callbackQuery()->data === 'delay') {

            $this->clearButtons();
            $this->menuText('Введите время в формате дд.мм.гггг чч:мм по МСК');
            $this->orNext('time');
            $this->showMenu();
            return;

        }

        if ($bot->callbackQuery()->data === 'instant') {

            $this->post->posting_time = now()->addSeconds(2);
            $this->send($bot);
            return;

        }
    }

    public function time(Nutgram $bot): void
    {
        try {

            $this->post->posting_time = Carbon::createFromFormat('d.m.Y H:i', $bot->message()->text);
            $this->send($bot);

        } catch (\Throwable) {

            $bot->sendMessage('Неверный формат');
            return;

        }
    }

    public function send(Nutgram $bot): void
    {
        $this->post->save();
        SendPost::dispatch($this->post)->delay($this->post->posting_time);

        $bot->sendMessage('Пост добавлен в очередь');
        $this->none($bot);
    }

    public function none(Nutgram $bot): void
    {
        $this->end();
    }
}
