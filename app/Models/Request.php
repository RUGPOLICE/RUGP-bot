<?php

namespace App\Models;

use App\Enums\RequestModule;
use App\Enums\RequestSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property Account|Chat|User $requestable
 * @property Token $token
 * @property RequestSource $source
 * @property RequestModule $module
 */
class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'requestable_type',
        'requestable_id',
        'token_id',
        'source',
        'module',
    ];

    public function casts(): array
    {
        return [
            'source' => \App\Enums\RequestSource::class,
            'module' => \App\Enums\RequestModule::class,
        ];
    }

    public int $migrationOrder = 3;
    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();

        $table->foreignIdFor(Token::class)->constrained()->cascadeOnDelete();
        $table->morphs('requestable');

        $table->string('source');
        $table->string('module');
    }


    public function requestable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function token(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Token::class);
    }


    public static function log(Account|Chat|User $requestable, Token $token, RequestSource $source, RequestModule $module): self
    {
        $request = new self;
        $request->token()->associate($token);
        $request->requestable()->associate($requestable);
        $request->source = $source;
        $request->module = $module;
        $request->save();
        return $request;
    }
}
