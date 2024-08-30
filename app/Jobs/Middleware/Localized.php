<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Localized
{
    public function handle(object $job, Closure $next): void
    {
        App::setLocale($job->language?->value ?? config('app.fallback_locale'));
        $next($job);
    }
}
