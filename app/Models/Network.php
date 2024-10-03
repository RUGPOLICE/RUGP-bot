<?php

namespace App\Models;

use App\Services\Network\GoplusService;
use App\Services\Network\NetworkService;
use App\Services\Network\SolanaService;
use App\Services\Network\TonService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property integer $id
 * @property string $slug
 * @property string $name
 * @property string $token
 * @property Collection $tokens
 * @property NetworkService $service
 * @method static self create()
 */
class Network extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'token',
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
        $table->integer('priority')->default(0);
    }

    public function tokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function service(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => app(match ($this->slug) {
                'ton' => TonService::class,
                'eth', 'bsc', 'base', 'tron' => GoplusService::class,
                'solana' => SolanaService::class,
                default => NetworkService::class,
            })
        );
    }

    public static function getDefault(): static
    {
        return static::query()->firstOrCreate(['slug' => 'ton'], ['name' => 'TON', 'token' => 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c', 'priority' => 10]);
    }
}
