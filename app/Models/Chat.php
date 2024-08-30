<?php

namespace App\Models;

use App\Enums\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property int $chat_id
 * @property boolean $is_show_warnings
 * @property Language $language
 */
class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'is_show_warnings',
        'language',
    ];

    public function casts(): array
    {
        return [
            'is_show_warnings' => 'boolean',
            'language' => Language::class,
        ];
    }

    public int $migrationOrder = 3;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->bigInteger('chat_id');
        $table->boolean('is_show_warnings')->default(true);
        $table->string('language')->default(Language::EN->value);
    }
}
