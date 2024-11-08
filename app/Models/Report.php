<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $message
 * @property Collection $files
 * @property Account $account
 */
class Report extends Model
{
    use HasFactory;

    protected $casts = [
        'files' => AsCollection::class,
    ];

    public int $migrationOrder = 3;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
        $table->text('message')->nullable();
        $table->json('files')->nullable();
        $table->timestamps();
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
