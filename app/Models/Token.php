<?php

namespace App\Models;

use App\Enums\Dex;
use App\Enums\Risk;
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
 *
 * @property float $dedust_tax_buy
 * @property float $dedust_tax_sell
 * @property float $dedust_tax_transfer
 *
 * @property float $stonfi_deprecated
 * @property float $stonfi_taxable
 *
 * @property Risk $risk_summary
 * @property Risk $risk_master
 * @property Risk $risk_wallet
 * @property Risk $risk_revoked
 * @property Risk $risk_dedust
 * @property Risk $risk_stonfi
 * @property Risk $risk_dedust_pool_exists
 * @property Risk $risk_dedust_buy
 * @property Risk $risk_dedust_sell
 * @property Risk $risk_dedust_transfer
 * @property Risk $risk_stonfi_pool_exists
 * @property Risk $risk_stonfi_deprecated
 * @property Risk $risk_stonfi_taxable
 * @property boolean $dedust_pool_exists
 * @property boolean $stonfi_pool_exists
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
        $table->string('image')->nullable();
        $table->string('description')->nullable();
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


    public function dedustPoolExists(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->pools()->where('dex', Dex::DEDUST)->exists());
    }

    public function stonfiPoolExists(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->pools()->where('dex', Dex::STONFI)->exists());
    }

    public function riskSummary(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => array_reduce(
            [
                $this->risk_master,
                $this->risk_wallet,
                $this->risk_dedust === Risk::UNKNOWN ? Risk::LOW : $this->risk_dedust,
                $this->risk_stonfi === Risk::UNKNOWN ? Risk::LOW : $this->risk_stonfi,
            ],
            fn ($carry, $item) => $carry->value > $item->value ? $carry : $item,
            Risk::LOW
        ));
    }

    public function riskMaster(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->is_known_master ? Risk::LOW : Risk::MEDIUM);
    }

    public function riskWallet(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->is_known_wallet ? Risk::LOW : Risk::MEDIUM);
    }

    public function riskRevoked(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->is_revoked ? Risk::LOW : Risk::HIGH);
    }

    public function riskDedust(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => array_reduce(
            [
                $this->risk_dedust_buy,
                $this->risk_dedust_sell,
                $this->risk_dedust_transfer,
                $this->risk_dedust_pool_exists,
            ],
            fn ($carry, $item) => $carry->value > $item->value ? $carry : $item,
            Risk::LOW
        ));
    }

    public function riskStonfi(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => array_reduce(
            [
                $this->risk_stonfi_deprecated,
                $this->risk_stonfi_taxable,
                $this->risk_stonfi_pool_exists,
            ],
            fn ($carry, $item) => $carry->value > $item->value ? $carry : $item,
            Risk::LOW
        ));
    }

    public function riskDedustPoolExists(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->dedust_pool_exists ? Risk::LOW : Risk::UNKNOWN);
    }

    public function riskDedustBuy(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => self::lossToRisk($this->dedust_tax_buy));
    }

    public function riskDedustSell(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => self::lossToRisk($this->dedust_tax_sell));
    }

    public function riskDedustTransfer(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => self::lossToRisk($this->dedust_tax_transfer));
    }

    public function riskStonfiPoolExists(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->stonfi_pool_exists ? Risk::LOW : Risk::UNKNOWN);
    }

    public function riskStonfiDeprecated(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->stonfi__deprecated ? Risk::LOW : Risk::MEDIUM);
    }

    public function riskStonfiTaxable(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->stonfi__taxable ? Risk::MEDIUM : Risk::LOW);
    }

    public function isRevoked(): Attribute
    {
        return Attribute::make(get: fn (?string $value, array $attributes) => $this->owner === '0:0000000000000000000000000000000000000000000000000000000000000000');
    }


    private static function lossToRisk(?float $loss): Risk
    {
        if ($loss === null) return Risk::UNKNOWN;
        if ($loss >= 0.5 || $loss < 0) return Risk::DANGER;
        if ($loss >= 0.2) return Risk::HIGH;
        if ($loss > 0) return Risk::MEDIUM;
        return Risk::LOW;
    }

    public function riskIcon(string $step): string
    {
        return Risk::icon($this->{'risk_' . $step}) . ' ';
    }
}
