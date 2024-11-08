<?php

namespace App\Models;

use App\Enums\Language;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property int $id
 * @property Carbon $last_active_at
 * @property string $telegram_id
 * @property string $telegram_username
 * @property boolean $is_blocked
 * @property boolean $is_show_warnings
 * @property boolean $is_show_scam
 * @property Language $language
 * @property Network $network
 */
class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'telegram_username',
        'last_active_at',
        'is_show_warnings',
        'is_show_scam',
        'language',
        'network_id',
    ];

    public function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'is_blocked' => 'boolean',
            'is_show_warnings' => 'boolean',
            'is_show_scam' => 'boolean',
            'language' => Language::class,
        ];
    }

    public int $migrationOrder = 3;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->timestamp('last_active_at');
        $table->foreignIdFor(Network::class)->nullable();

        $table->string('telegram_id')->unique();
        $table->string('telegram_username')->nullable();

        $table->boolean('is_blocked')->default(false);
        $table->boolean('is_show_warnings')->default(true);
        $table->boolean('is_show_scam')->default(false);
        $table->string('language')->default(Language::EN->value);
    }

    public function network(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
