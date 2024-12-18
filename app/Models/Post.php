<?php

namespace App\Models;

use App\Enums\Target;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * @property int $id
 * @property string $target_id
 * @property Target $target
 * @property string $text
 * @property string $image
 * @property string $buttons
 * @property Carbon $posting_time
 * @property boolean $is_test
 * @property ?InlineKeyboardMarkup $markup
 */
class Post extends Model
{
    use HasFactory;

    protected $casts = [
        'target' => Target::class,
        'posting_time' => 'datetime',
        'is_test' => 'boolean',
    ];

    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->string('target_id')->nullable();

        $table->string('target');
        $table->text('text');

        $table->string('image')->nullable();
        $table->string('buttons')->nullable();

        $table->timestamp('posting_time');
        $table->timestamps();
    }

    public function markup(): Attribute
    {
        return Attribute::make(get: function (?string $value, array $attributes) {
            if (!$this->buttons)
                return null;

            $markup = InlineKeyboardMarkup::make();
            foreach (explode("\n", $this->buttons) as $button) {

                [$text, $url] = explode('-', $button);
                $url = trim($url);
                $markup->addRow(InlineKeyboardButton::make(trim($text), url: $url));

            }

            return $markup;
        });
    }
}
