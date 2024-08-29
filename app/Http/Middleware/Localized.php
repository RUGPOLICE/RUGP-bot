<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class Localized
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($request->get('lang', config('app.fallback_locale')));
        return $next($request);
    }
}
