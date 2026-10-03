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
