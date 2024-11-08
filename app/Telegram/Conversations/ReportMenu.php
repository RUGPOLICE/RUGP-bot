<?php

namespace App\Telegram\Conversations;

use App\Jobs\Blackbox\SendReport;
use App\Models\Report;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

class ReportMenu extends ImagedEditableInlineMenu
{
    public Report $report;

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
        $this->report = new Report;
        $this->report->account()->associate($bot->get('account'));
        $this->report->save();

        $this
            ->clearButtons()
            ->menuText(__('blackbox.text.start'))
            ->addButtonRow(InlineKeyboardButton::make(__('blackbox.buttons.back'), callback_data: 'back@menu'))
            ->orNext('message')
            ->showMenu();
    }

    public function message(Nutgram $bot): void
    {
        if ($bot->message()->document && $bot->message()->document->file_size / 1024 ** 2 <= 50) {
            $this->saveDocument(
                $bot->message()->document->file_name,
                $bot->message()->document->file_id,
            );
        }

        if ($bot->message()->text) {
            $this->report->message = $bot->message()->text;
            $this->report->save();
        }

        $buttons = [InlineKeyboardButton::make(__('blackbox.buttons.cancel'), callback_data: 'back@menu')];
        if ($this->report->message) $buttons[] = InlineKeyboardButton::make(__('blackbox.buttons.send'), callback_data: 'null@send');

        $this
            ->clearButtons()
            ->menuText(__('blackbox.text.prepared', [
                'message' => $this->report->message ?: __('blackbox.text.need_text'),
                'files' => $this->report->files ? $this->report->files->map(fn (array $file) => $file['name'])->implode("\n") : __('blackbox.text.files_empty'),
            ]))
            ->addButtonRow(... $buttons)
            ->orNext('message')
            ->showMenu();
    }

    public function send(Nutgram $bot): void
    {
        SendReport::dispatch($this->report, $bot->get('account')->language);
        $bot->sendMessage(__('blackbox.text.sent'));

        $this->end();
        HomeMenu::begin($bot);
    }

    public function menu(Nutgram $bot): void
    {
        $this->report->delete();
        $this->end();

        match ($bot->callbackQuery()->data) {
            'back' => HomeMenu::begin($bot),
        };
    }

    private function saveDocument(string $file_name, string $file_id): void
    {
        if (!$this->report->files)
            $this->report->files = collect();

        $this->report->files->push([
            'name' => $file_name,
            'file' => $file_id,
        ]);

        $this->report->save();
    }
}
