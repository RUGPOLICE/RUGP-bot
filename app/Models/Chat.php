<?php

namespace App\Models;

use App\Enums\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property int $chat_id
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
        'chat_id',
        'is_show_warnings',
        'is_show_scam',
        'language',
        'network_id',
    ];

    public function casts(): array
    {
        return [
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
        $table->foreignIdFor(Network::class)->nullable();

        $table->bigInteger('chat_id');
        $table->boolean('is_blocked')->default(false);
        $table->boolean('is_show_warnings')->default(true);
        $table->boolean('is_show_scam')->default(true);
        $table->string('language')->default(Language::EN->value);
    }

    public function network(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
