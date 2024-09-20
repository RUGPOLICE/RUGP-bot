<?php

namespace App\Models;

use App\Services\GeckoTerminalService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;

/**
 * @property integer $id
 * @property string $address
 * @property Carbon $scanned_at
 *
 * @property string $name
 * @property string $symbol
 * @property string $owner
 * @property string $image
 * @property string $description
 * @property integer $holders_count
 * @property integer $supply
 * @property \Illuminate\Support\Collection $holders
 * @property array $websites
 * @property array $socials
 *
 * @property boolean $is_known_master
 * @property boolean $is_known_wallet
 * @property boolean $is_revoked
 *
 * @property boolean $is_warn_honeypot
 * @property boolean $is_warn_rugpull
 * @property boolean $is_warn_original
 * @property boolean $is_warn_scam
 * @property boolean $is_warn_liquidity_stonfi
 * @property boolean $is_warn_liquidity_dedust
 * @property boolean $is_warn_liquidity
 * @property boolean $is_warn_burned
 *
 * @property string $description_formatted
 *
 * @property Collection $pools
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
        'is_warn_honeypot',
        'is_warn_rugpull',
        'is_warn_original',
        'is_warn_scam',
        'is_warn_liquidity_stonfi',
        'is_warn_liquidity_dedust',
        'is_warn_liquidity',
        'is_warn_burned',
    ];

    public function casts(): array
    {
        return [
            'holders' => AsCollection::class,
            'scanned_at' => 'datetime',
            'websites' => 'array',
            'socials' => 'array',
            'is_known_master' => 'boolean',
            'is_known_wallet' => 'boolean',
            'is_warn_honeypot' => 'boolean',
            'is_warn_rugpull' => 'boolean',
            'is_warn_original' => 'boolean',
            'is_warn_scam' => 'boolean',
            'is_warn_liquidity_stonfi' => 'boolean',
            'is_warn_liquidity_dedust' => 'boolean',
            'is_warn_liquidity' => 'boolean',
            'is_warn_burned' => 'boolean',
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
        $table->timestamp('scanned_at')->nullable();

        $table->string('name')->nullable();
        $table->string('symbol')->nullable();
        $table->string('owner')->nullable();
        $table->text('image')->nullable();
        $table->text('description')->nullable();
        $table->integer('holders_count')->nullable();
        $table->double('supply')->nullable();
        $table->json('holders')->nullable();
        $table->json('websites')->nullable();
        $table->json('socials')->nullable();

        $table->boolean('is_warn_honeypot')->default(false);
        $table->boolean('is_warn_rugpull')->default(false);
        $table->boolean('is_warn_original')->default(false);
        $table->boolean('is_warn_scam')->default(false);
        $table->boolean('is_warn_liquidity_stonfi')->default(false);
        $table->boolean('is_warn_liquidity_dedust')->default(false);
        $table->boolean('is_warn_liquidity')->default(false);
        $table->boolean('is_warn_burned')->default(false);
    }

    public function pools(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pool::class);
    }

    public function reactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reaction::class);
    }


    public function descriptionFormatted(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => mb_strcut($this->description, 0, 150) . (mb_strlen($this->description) > 150 ? '...' : ''));
    }

    public function isRevoked(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->owner === '0:0000000000000000000000000000000000000000000000000000000000000000');
    }


    public static function getAddress(string $address): array
    {
        if ($address[0] === '$') {

            $symbol = mb_substr($address, 1);
            $token = Token::query()->orderByDesc('is_warn_original')->orderByDesc('holders_count');

            $force = config('app.tokens.force');
            $forceTokens = [];

            foreach ($force as $item) {
                [$s, $t] = explode(':', $item);
                $forceTokens[$s] = $t;
            }

            if (in_array(strtolower($symbol), array_keys($forceTokens)))
                $token = $token->where('address', $forceTokens[strtolower($symbol)]);
            else
                $token = $token->where('symbol', $symbol);

            $token = $token->first();
            if ($token) return ['success' => true, 'address' => $token->address];
            return ['success' => false, 'error' => __('telegram.errors.address.symbol')];

        }

        if (mb_strlen($address) < 48)
            return ['success' => false, 'error' => __('telegram.errors.address.invalid')];

        $address = explode('/', $address);
        $address = $address[count($address) - 1];

        if (mb_strpos($address, '?') !== false)
            $address = mb_substr($address, 0, mb_strpos($address, '?'));

        $service = App::make(GeckoTerminalService::class);
        $address = $service->getTokenAddressByQuery($address);

        if (!$address)
            return ['success' => false, 'error' => __('telegram.errors.address.empty')];

        return ['success' => true, 'address' => $address];
    }
}
