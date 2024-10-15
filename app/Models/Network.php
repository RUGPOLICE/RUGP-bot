<?php

namespace App\Models;

use App\Jobs\Scanner\ScanTokenEthereum;
use App\Jobs\Scanner\ScanTokenSolana;
use App\Jobs\Scanner\ScanTokenTon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * @property integer $id
 * @property string $slug
 * @property string $name
 * @property string $token
 * @property string $explorer
 * @property Collection $tokens
 * @property Dispatchable $job
 * @method static self create()
 */
class Network extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'token',
        'explorer',
        'priority',
    ];

    public int $migrationOrder = 1;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->string('slug')->unique();
        $table->string('name');
        $table->string('token');
        $table->string('explorer')->nullable();
        $table->integer('priority')->default(0);
    }

    public function tokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function job(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => app(match ($this->slug) {
                'solana' => ScanTokenSolana::class,
                'eth', 'bsc', 'base', 'tron' => ScanTokenEthereum::class,
                default => ScanTokenTon::class,
            })
        );
    }

    public static function getDefault(): static
    {
        return static::query()->firstOrCreate(['slug' => 'ton'], ['name' => 'TON', 'token' => 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c', 'priority' => 10]);
    }
}
