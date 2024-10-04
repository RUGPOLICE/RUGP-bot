<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property integer $id
 * @property string $slug
 * @property string $name
 * @property string $link
 * @property Collection $pools
 * @method static self create()
 */
class Dex extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'link',
    ];

    public int $migrationOrder = 1;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->string('slug')->unique();
        $table->string('name')->default('DexName');
        $table->string('link')->default('https://example.com');
    }

    public function pools(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pool::class);
    }

    public function getLink(string $address): string
    {
        return "$this->link/$address";
    }
}
