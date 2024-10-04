<?php

namespace App\Models;

use App\Enums\Lock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property integer $id
 * @property Carbon $created_at
 * @property string $address
 * @property Dex $dex
 * @property array $holders
 * @property integer $supply
 * @property float $price
 * @property float $fdv
 * @property float $reserve
 * @property float $m5_volume
 * @property float $m5_price_change
 * @property integer $m5_buys
 * @property integer $m5_sells
 * @property float $h1_volume
 * @property float $h1_price_change
 * @property integer $h1_buys
 * @property integer $h1_sells
 * @property float $h6_volume
 * @property float $h6_price_change
 * @property integer $h6_buys
 * @property integer $h6_sells
 * @property float $h24_volume
 * @property float $h24_price_change
 * @property integer $h24_buys
 * @property integer $h24_sells
 * @property integer $burned_amount
 * @property float $burned_percent
 * @property Lock $locked_type
 * @property integer $locked_amount
 * @property float $locked_percent
 * @property boolean $locked_dyor
 * @property float $tax_buy
 * @property float $tax_sell
 * @property float $tax_transfer
 * @property Carbon $unlocks_at
 *
 * @property string $price_formatted
 */
class Pool extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'holders',
        'supply',
        'price',
        'fdv',
        'reserve',
        'm5_volume',
        'm5_price_change',
        'm5_buys',
        'm5_sells',
        'h1_volume',
        'h1_price_change',
        'h1_buys',
        'h1_sells',
        'h6_volume',
        'h6_price_change',
        'h6_buys',
        'h6_sells',
        'h24_volume',
        'h24_price_change',
        'h24_buys',
        'h24_sells',
        'burned_amount',
        'burned_percent',
        'locked_type',
        'locked_amount',
        'locked_percent',
        'locked_dyor',
        'unlocks_at',
        'tax_buy',
        'tax_sell',
        'tax_transfer',
        'token_id',
        'dex_id',
        'created_at',
    ];

    public function casts(): array
    {
        return [
            'holders' => AsCollection::class,
            'locked_type' => Lock::class,
            'unlocks_at' => 'datetime',
        ];
    }

    public int $migrationOrder = 3;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->foreignIdFor(Token::class)->constrained()->cascadeOnDelete();
        $table->foreignIdFor(Dex::class)->nullable();

        $table->string('address')->unique();
        $table->json('holders')->nullable();

        $table->double('supply')->nullable();
        $table->double('price')->nullable();
        $table->double('fdv')->nullable();
        $table->double('reserve')->nullable();

        $table->double('m5_volume')->nullable();
        $table->double('m5_price_change')->nullable();
        $table->integer('m5_buys')->nullable();
        $table->integer('m5_sells')->nullable();

        $table->double('h1_volume')->nullable();
        $table->double('h1_price_change')->nullable();
        $table->integer('h1_buys')->nullable();
        $table->integer('h1_sells')->nullable();

        $table->double('h6_volume')->nullable();
        $table->double('h6_price_change')->nullable();
        $table->integer('h6_buys')->nullable();
        $table->integer('h6_sells')->nullable();

        $table->double('h24_volume')->nullable();
        $table->double('h24_price_change')->nullable();
        $table->integer('h24_buys')->nullable();
        $table->integer('h24_sells')->nullable();

        $table->double('burned_amount')->nullable();
        $table->decimal('burned_percent')->nullable();

        $table->integer('locked_type')->nullable();
        $table->double('locked_amount')->nullable();
        $table->decimal('locked_percent')->nullable();
        $table->boolean('locked_dyor')->nullable();
        $table->timestamp('unlocks_at')->nullable();

        $table->decimal('tax_buy')->nullable();
        $table->decimal('tax_sell')->nullable();
        $table->decimal('tax_transfer')->nullable();
    }

    public function token(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    public function dex(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Dex::class);
    }

    public function priceFormatted(): Attribute
    {
        return Attribute::make(
            get: function (?float $price) {
                $price = number_format($this->price, 20);
                return mb_strcut($price, 0, mb_strpos($price, '.') + 12);
            },
        );
    }
}
