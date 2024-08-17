<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property Account $account
 * @property Token $token
 */
class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'token_id',
        'type',
    ];

    public function casts(): array
    {
        return [
            'type' => \App\Enums\Reaction::class,
        ];
    }

    public int $migrationOrder = 3;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
        $table->foreignIdFor(Token::class)->constrained()->cascadeOnDelete();
        $table->string('type');
    }


    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function token(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Token::class);
    }
}
