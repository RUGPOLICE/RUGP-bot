<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Localized
{
    public function handle(object $job, Closure $next): void
    {
        if (isset($job->language))
            App::setLocale($job->language->value);
        $next($job);
    }
}
