<?php

namespace App\Models;

use App\Enums\Dex;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property integer $id
 * @property Carbon $created_at
 * @property string $address
 * @property Dex $dex
 * @property float $price
 * @property float $fdv
 * @property float $reserve
 * @property float $h24_volume
 * @property float $h24_price_change
 * @property integer $h24_buys
 * @property integer $h24_sells
 * @property integer $burned_amount
 * @property integer $locked_amount
 * @property float $burned_percent
 * @property float $locked_percent
 * @property Carbon $unlocks_at
 */
class Pool extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'dex',
        'price',
        'fdv',
        'reserve',
        'h24_volume',
        'h24_price_change',
        'h24_buys',
        'h24_sells',
        'burned_amount',
        'locked_amount',
        'burned_percent',
        'locked_percent',
        'token_id',
        'created_at',
        'unlocks_at',
    ];

    public function casts(): array
    {
        return [
            'dex' => Dex::class,
            'unlocks_at' => 'datetime',
        ];
    }

    public int $migrationOrder = 2;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->foreignIdFor(Token::class)->constrained()->cascadeOnDelete();

        $table->string('address')->unique();
        $table->string('dex');

        $table->double('price')->nullable();
        $table->double('fdv')->nullable();
        $table->double('reserve')->nullable();
        $table->double('h24_volume')->nullable();
        $table->double('h24_price_change')->nullable();
        $table->integer('h24_buys')->nullable();
        $table->integer('h24_sells')->nullable();
        $table->bigInteger('burned_amount')->nullable();
        $table->bigInteger('locked_amount')->nullable();
        $table->decimal('burned_percent')->nullable();
        $table->decimal('locked_percent')->nullable();
        $table->timestamp('unlocks_at')->nullable();
    }

    public function token(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Token::class);
    }
}
