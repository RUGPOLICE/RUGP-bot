<?php

namespace App\Models;

use App\Enums\Frame;
use App\Enums\Language;
use App\Telegram\Conversations\GptMenu;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property Carbon $last_active_at
 * @property string $telegram_id
 * @property string $telegram_username
 * @property string $telegram_name
 * @property int $gpt_count
 * @property Language $language
 * @property Frame $frame
 * @property boolean $is_blocked
 * @property boolean $is_shown_language
 * @property boolean $is_shown_rules
 * @property boolean $is_show_warnings
 * @property boolean $is_show_scam
 * @property boolean $is_show_chart_text
 * @property Network $network
 */
class Account extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'telegram_id',
        'telegram_username',
        'telegram_name',
        'last_active_at',
        'gpt_count',
        'frame',
        'is_show_chart_text',
        'network_id',
    ];

    protected function casts(): array
    {
        return [
            'language' => Language::class,
            'frame' => Frame::class,
            'last_active_at' => 'datetime',
            'is_blocked' => 'boolean',
            'is_show_warnings' => 'boolean',
            'is_show_scam' => 'boolean',
            'is_show_chart_text' => 'boolean',
        ];
    }

    public int $migrationOrder = 2;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->timestamp('last_active_at')->nullable();
        $table->foreignIdFor(Network::class)->nullable();

        $table->string('telegram_id')->unique();
        $table->string('telegram_username')->nullable();
        $table->string('telegram_name')->nullable();
        $table->boolean('is_blocked')->default(false);
        $table->integer('gpt_count')->default(GptMenu::MAX_ATTEMPTS);

        $table->boolean('is_shown_language')->default(false);
        $table->boolean('is_shown_rules')->default(false);

        $table->string('language')->default(Language::EN->value);
        $table->string('frame')->default(Frame::DAY->value);

        $table->boolean('is_show_warnings')->default(false);
        $table->boolean('is_show_scam')->default(false);
        $table->boolean('is_show_chart_text')->default(true);
    }


    public function reactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function network(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
