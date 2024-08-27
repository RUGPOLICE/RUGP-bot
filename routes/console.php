<?php


use App\Jobs\ExplorePools;
use Illuminate\Support\Facades\Schedule;

// Schedule::job(new ExplorePools, connection: 'redis')->everyTenMinutes();
Schedule::command('queue:prune-batches')->daily();
