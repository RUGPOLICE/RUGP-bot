<?php

use Illuminate\Support\Facades\Route;

Route::post('telegram', [\App\Http\Controllers\Api\TelegramController::class, 'handle']);
Route::post('telegramGroup', [\App\Http\Controllers\Api\TelegramController::class, 'group']);

Route::prefix('/v1')
    ->middleware(['auth:sanctum', \App\Http\Middleware\Localized::class])
    ->group(function () {

        Route::prefix('/{network}')->group(function () {

            Route::get('/token/{address}', [\App\Http\Controllers\Api\TokenController::class, 'info']);

        });

});
