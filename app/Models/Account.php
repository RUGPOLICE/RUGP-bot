<?php

namespace App\Models;

use App\Enums\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $telegram_id
 * @property string $telegram_username
 * @property Language $language
 * @property boolean $is_blocked
 * @property boolean $is_shown_language
 * @property boolean $is_shown_rules
 * @property boolean $is_hide_warnings
 * @property boolean $is_show_scam
 * @property Network $network
 */
class Account extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'telegram_id',
        'telegram_username',
        'network_id',
    ];

    protected function casts(): array
    {
        return [
            'language' => Language::class,
            'is_blocked' => 'boolean',
            'is_hide_warnings' => 'boolean',
            'is_show_scam' => 'boolean',
        ];
    }

    public int $migrationOrder = 2;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->foreignIdFor(Network::class)->nullable();

        $table->string('telegram_id')->unique();
        $table->string('telegram_username')->nullable();
        $table->boolean('is_blocked')->default(false);

        $table->boolean('is_shown_language')->default(false);
        $table->boolean('is_shown_rules')->default(false);

        $table->string('language')->default(Language::EN->value);
        $table->boolean('is_hide_warnings')->default(false);
        $table->boolean('is_show_scam')->default(true);
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
