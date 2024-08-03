<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property Account $account
 * @property Token $token
 * @property string $message_id
 */
class Pending extends Model
{
    use HasFactory;

    public int $migrationOrder = 3;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
        $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
        $table->foreignIdFor(Token::class)->constrained()->cascadeOnDelete();
        $table->string('message_id');
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
