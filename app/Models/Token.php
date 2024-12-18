<?php

namespace App\Models;

use App\Jobs\Scanner\SendScamPost;
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
 * @property integer $decimals
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
 * @property boolean $is_warn_liquidity
 * @property boolean $is_warn_burned
 *
 * @property string $description_formatted
 *
 * @property Network $network
 * @property Collection $pools
 * @method static self find(int $id)
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
        'decimals',
        'holders',
        'websites',
        'socials',
        'is_known_master',
        'is_known_wallet',
        'is_warn_honeypot',
        'is_warn_rugpull',
        'is_warn_original',
        'is_warn_scam',
        'is_warn_liquidity',
        'is_warn_burned',
        'network_id',
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
            'is_warn_liquidity' => 'boolean',
            'is_warn_burned' => 'boolean',
        ];
    }

    public int $migrationOrder = 2;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->foreignIdFor(Network::class)->nullable();

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
        $table->integer('decimals')->default(9);
        $table->json('holders')->nullable();
        $table->json('websites')->nullable();
        $table->json('socials')->nullable();

        $table->boolean('is_warn_honeypot')->default(false);
        $table->boolean('is_warn_rugpull')->default(false);
        $table->boolean('is_warn_original')->default(false);
        $table->boolean('is_warn_scam')->default(false);
        $table->boolean('is_warn_liquidity')->default(false);
        $table->boolean('is_warn_burned')->default(false);
    }

    public function network(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Network::class);
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


    public function sendNotification(Chat|Account|null $source = null): void
    {
        if ($this->isDirty(['is_warn_honeypot', 'is_warn_rugpull', 'is_warn_scam'])) {

            $delay = now();
            $chats = Chat::query()->where('is_blocked', false)->where('is_show_scam', true);
            $accounts = Account::query()->where('is_blocked', false)->where('is_show_scam', true);

            if ($source instanceof Chat) $chats = $chats->whereNot('id', $source->id);
            if ($source instanceof Account) $accounts = $accounts->whereNot('id', $source->id);

            foreach ($chats->get() as $chat)
                SendScamPost::dispatch($this, $chat, $chat->language)->delay($delay = $delay->addSecond());

            foreach ($accounts->get() as $account)
                SendScamPost::dispatch($this, $account, $account->language)->delay($delay = $delay->addSecond());

        }
    }


    public static function getAddress(string $address, ?Network $priority_network = null): array
    {
        $network = Network::getDefault();
        @[$address, $explicit_network] = explode(' ', $address);

        if ($priority_network) $network = $priority_network;
        if ($explicit_network) $network = Network::query()->where('slug', strtolower($explicit_network))->orWhere('name', strtolower($explicit_network))->orWhere('alias', strtolower($explicit_network))->first();

        if ($address[0] === '$') {

            $symbol = mb_substr($address, 1);
            $token = ($network ?? Network::getDefault())->tokens()->orderByDesc('is_warn_original')->orderByDesc('holders_count');

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
            if ($token) return ['success' => true, 'address' => $token->address, 'network' => $network->slug];
            return ['success' => false, 'error' => __('telegram.errors.address.symbol')];

        }

        // if (mb_strlen($address) < 48)
        //     return ['success' => false, 'error' => __('telegram.errors.address.invalid')];

        $address = explode('/', $address);
        $address = $address[count($address) - 1];

        if (mb_strpos($address, '?') !== false)
            $address = mb_substr($address, 0, mb_strpos($address, '?'));

        $service = App::make(GeckoTerminalService::class);
        [$network, $address] = $service->getTokenAddressByQuery($address, $network);

        if (!$address)
            return ['success' => false, 'error' => __('telegram.errors.address.empty')];

        return ['success' => true, 'address' => $address, 'network' => $network->slug];
    }
}
