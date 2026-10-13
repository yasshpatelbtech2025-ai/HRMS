<?php

use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/context/companies', [CompanyContextController::class, 'index'])->name('context.companies');
    Route::post('/context/company', CompanyContextController::class)->name('context.company');
    Route::get('/search', SearchController::class)->name('search');

    Route::patch('company/{company}/restore', [CompanyController::class, 'restore'])->withTrashed()->name('company.restore');
    Route::resource('company', CompanyController::class)->except('show');
    Route::get('employees/separated', [EmployeeController::class, 'separated'])->name('employees.separated');
    Route::delete('employees/bulk', [EmployeeController::class, 'bulkDestroy'])->name('employees.bulk-destroy');
    Route::patch('employees/bulk/restore', [EmployeeController::class, 'bulkRestore'])->name('employees.bulk-restore');
    Route::patch('employees/{employee}/restore', [EmployeeController::class, 'restore'])->withTrashed()->name('employees.restore');
    Route::resource('employees', EmployeeController::class)->except('show');
    Route::patch('departments/{department}/restore', [DepartmentController::class, 'restore'])->withTrashed()->name('departments.restore');
    Route::resource('departments', DepartmentController::class)->except('show');
    Route::patch('positions/{position}/restore', [PositionController::class, 'restore'])->withTrashed()->name('positions.restore');
    Route::resource('positions', PositionController::class)->except('show');

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

    foreach ([
        'missing-punch' => 'Missing punch',
        'requests' => 'Requests',
        'leave' => 'Leave',
        'shifts' => 'Shifts',
        'holidays' => 'Holidays',
    ] as $uri => $title) {
        Route::get("/attendance/{$uri}", fn () => view('placeholder', ['title' => $title]))->name("attendance.{$uri}");
    }
});

// Component reference and the attendance grid prototype: local environment only.
Route::middleware('auth')->prefix('_ui')->group(function () {
    Route::get('/', fn () => app()->isLocal() ? view('ui-kit.index') : abort(404))->name('ui-kit');
    Route::get('/attendance', fn () => app()->isLocal() ? view('ui-kit.attendance') : abort(404))->name('ui-kit.attendance');
});

require __DIR__.'/auth.php';
