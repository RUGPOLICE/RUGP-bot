<?php


use App\Jobs\Scanner\ApiStatus;
use App\Jobs\Scanner\UpdateGptCounts;
use Illuminate\Support\Facades\Schedule;

// Schedule::job(new ExplorePools, connection: 'redis')->everyTenMinutes();
Schedule::job(new ApiStatus, connection: 'redis')->everyFiveMinutes();
Schedule::job(new UpdateGptCounts(), connection: 'redis')->daily()->at('21:00');
Schedule::command('queue:prune-batches')->daily();
