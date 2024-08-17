<?php

namespace App\Models;

use App\Enums\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $telegram_id
 * @property string $telegram_username
 * @property Language $language
 * @property boolean $is_shown_language
 * @property boolean $is_shown_rules
 */
class Account extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'telegram_id',
        'telegram_username',
    ];

    protected function casts(): array
    {
        return [
            'language' => Language::class,
        ];
    }

    public int $migrationOrder = 2;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();

        $table->string('telegram_id')->unique();
        $table->string('telegram_username')->nullable();
        $table->string('language')->default(Language::RU->value);

        $table->boolean('is_shown_language')->default(false);
        $table->boolean('is_shown_rules')->default(false);
    }


    public function reactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reaction::class);
    }
}
