<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Compliance › To Do List: create the tasks falling due and complete the done ones.
Artisan::command('compliance:sync-tasks', function (App\Services\Compliance\ComplianceTasks $tasks) {
    App\Models\Company::all()->each(fn ($company) => $tasks->sync($company));
    $this->info('Compliance tasks synced.');
})->purpose('Create and complete the compliance To Do List tasks for every company');

Illuminate\Support\Facades\Schedule::command('compliance:sync-tasks')->dailyAt('06:00');

// Recently deleted records older than 30 days are removed for good (App\Models\Concerns\Trashable).
Illuminate\Support\Facades\Schedule::command('model:prune')->dailyAt('03:00');

// Background jobs (employee imports, emails). Production should run a queue worker
// (php artisan queue:work, kept alive by Supervisor or the host); this also works
// through anything left in the queue each minute on hosts with only the scheduler cron.
Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=1')->everyMinute()->withoutOverlapping();
