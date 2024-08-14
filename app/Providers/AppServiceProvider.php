<?php

namespace App\Providers;

use App\Telegram\Mixins\ImagedMessage;
use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Nutgram::mixin(new ImagedMessage);
    }
}
