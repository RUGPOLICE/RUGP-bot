<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Localized
{
    public function handle(object $job, Closure $next): void
    {
        if (isset($job->account))
            App::setLocale($job->account->language->value);

        $next($job);
    }
}
