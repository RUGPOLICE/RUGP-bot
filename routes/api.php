<?php

use Illuminate\Support\Facades\Route;

Route::post('telegram', [\App\Http\Controllers\Api\TelegramController::class, 'handle']);

Route::prefix('/v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        Route::prefix('/{network}')->group(function () {

            Route::get('/token/{address}', [\App\Http\Controllers\Api\TokenController::class, 'info']);
            Route::get('/token/{address}/simulate', [\App\Http\Controllers\Api\TokenController::class, 'simulate']);

        });

});
