<?php

namespace App\Telegram\Conversations;

use App\Jobs\SendPost;
use App\Models\Post;
use Carbon\Carbon;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

class PostsMenu extends ImagedEditableInlineMenu
{
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
        $post = new Post;
        $bot->setUserData('post', $post);

        $this->clearButtons();
        $this->menuText('Введите текст, поддержка HTML разметки присутствует');
        $this->orNext('text');
        $this->showMenu();
    }

    public function text(Nutgram $bot): void
    {
        $text = $bot->message()->text;
        if (!$text) {

            $bot->sendMessage('Введите текст');
            return;

        }

        $post = $bot->getUserData('post', default: new Post);
        $post->text = $text;
        $bot->setUserData('post', $post);

        $this->clearButtons();
        $this->menuText('Отправьте картинку');
        $this->addButtonRow(InlineKeyboardButton::make('Пропустить', callback_data: 'null@image'));
        $this->orNext('image');
        $this->showMenu();
    }

    public function image(Nutgram $bot): void
    {
        if ($bot->message()->photo) {

            $count = count($bot->message()->photo);
            $photo = $bot->message()->photo[$count - 1];

            $fileName = "posts/$photo->file_unique_id.jpg";
            $bot->getFile($photo->file_id)->saveToDisk($fileName);

            $post = $bot->getUserData('post', default: new Post);
            $post->image = $fileName;
            $bot->setUserData('post', $post);

        }

        $this->clearButtons();
        $this->menuText('Введите кнопки в формате "Текст - ссылка", каждая с новой строки.');
        $this->addButtonRow(InlineKeyboardButton::make('Пропустить', callback_data: 'null@buttons'));
        $this->orNext('buttons');
        $this->showMenu();
    }

    public function buttons(Nutgram $bot): void
    {
        $buttons = $bot->message()->text;
        $wrong = ['Введите кнопки в формате "Текст - ссылка", каждая с новой строки.'];

        if ($buttons && !in_array($buttons, $wrong)) {

            $post = $bot->getUserData('post', default: new Post);
            $post->buttons = $buttons;
            $bot->setUserData('post', $post);

        }

        $this->clearButtons();
        $this->menuText('Выберите тип публикации');
        $this->addButtonRow(InlineKeyboardButton::make('Мгновенно', callback_data: 'instant@prepare'));
        $this->addButtonRow(InlineKeyboardButton::make('Тест', callback_data: 'test@prepare'));
        $this->addButtonRow(InlineKeyboardButton::make('Выбрать время', callback_data: 'delay@prepare'));
        $this->addButtonRow(InlineKeyboardButton::make('Выход', callback_data: 'none'));
        $this->showMenu();
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

            $post = $bot->getUserData('post', default: new Post);
            $post->posting_time = now()->addSeconds(2);
            $bot->setUserData('post', $post);

            $this->send($bot);
            return;
        }

        if ($bot->callbackQuery()->data === 'test') {

            $post = $bot->getUserData('post', default: new Post);
            $post->posting_time = now()->addSeconds(2);
            $post->is_test = true;
            $bot->setUserData('post', $post);

            $this->send($bot);
            return;
        }
    }

    public function time(Nutgram $bot): void
    {
        $post = $bot->getUserData('post', default: new Post);
        try {

            $post->posting_time = Carbon::createFromFormat('d.m.Y H:i', $bot->message()->text)/*->subHours(3)*/;

        } catch (\Throwable) {

            $bot->sendMessage('Неверный формат');
            return;

        }

        $bot->setUserData('post', $post);
        $this->send($bot);
    }

    public function send(Nutgram $bot): void
    {
        $post = $bot->getUserData('post', default: new Post);
        $post->save();

        SendPost::dispatch($post)->delay($post->posting_time);

        $bot->sendMessage('Пост добавлен в очередь');
        $this->none($bot);
    }

    public function none(Nutgram $bot): void
    {
        $bot->deleteUserData('post');
        $this->end();
    }
}
