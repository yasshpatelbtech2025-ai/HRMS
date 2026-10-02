<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('company', CompanyController::class)->except('show');
    Route::resource('employees', EmployeeController::class);

    // Sections still to be built render a placeholder page; each is replaced
    // by its real controller as the module lands.
    foreach ([
        'attendance' => 'Attendance',
        'compliance' => 'Compliance',
        'documents' => 'Documents',
        'payroll' => 'Payroll',
        'reports' => 'Reports',
        'configs' => 'Configs',
    ] as $uri => $title) {
        Route::get("/{$uri}", fn () => view('placeholder', ['title' => $title]))->name("{$uri}.index");
    }
});

require __DIR__.'/auth.php';
