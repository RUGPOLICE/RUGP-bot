<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property integer $id
 * @property string $address
 * @property boolean $is_scanned
 *
 * @property string $name
 * @property string $symbol
 * @property string $owner
 * @property string $image
 * @property string $description
 * @property integer $holders_count
 * @property integer $supply
 * @property array $holders
 * @property array $websites
 * @property array $socials
 *
 * @property boolean $is_known_master
 * @property boolean $is_known_wallet
 * @property boolean $is_revoked
 *
 * @property Pool[] $pools
 */
class Token extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'name',
        'symbol',
        'owner',
        'image',
        'description',
        'holders_count',
        'supply',
        'holders',
        'websites',
        'socials',
    ];

    public function casts(): array
    {
        return [
            'holders' => AsCollection::class,
            'websites' => 'array',
            'socials' => 'array',
        ];
    }

    public int $migrationOrder = 1;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();

        $table->string('address')->unique();
        $table->boolean('is_known_master')->default(false);
        $table->boolean('is_known_wallet')->default(false);

        $table->boolean('is_scanned')->default(false);
        $table->timestamp('scanned_at')->nullable();

        $table->string('name')->nullable();
        $table->string('symbol')->nullable();
        $table->string('owner')->nullable();
        $table->text('image')->nullable();
        $table->text('description')->nullable();
        $table->integer('holders_count')->nullable();
        $table->bigInteger('supply')->nullable();
        $table->json('holders')->nullable();
        $table->json('websites')->nullable();
        $table->json('socials')->nullable();
    }

    public function pools(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pool::class);
    }

    public function pendings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pending::class);
    }

    public function reactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reaction::class);
    }


    public function isRevoked(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->owner === '0:0000000000000000000000000000000000000000000000000000000000000000');
    }
}
