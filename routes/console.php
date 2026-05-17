<?php

use App\Contracts\MonitorRepositoryInterface;
use App\Jobs\CheckMonitorJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Uptime Monitor Scheduler
|--------------------------------------------------------------------------
| Runs every minute. Fetches monitors that are due for their next check
| and dispatches a queued job for each one. The scheduler returns
| immediately — actual HTTP checks happen asynchronously in the queue.
*/

Schedule::call(function () {
    $monitors = app(MonitorRepositoryInterface::class)->allDueForCheck();

    foreach ($monitors as $monitor) {
        CheckMonitorJob::dispatch($monitor);
    }
})->everyMinute()->name('dispatch-monitor-checks');
