<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Placeholder pages until each module is built.
Route::middleware(['auth', 'verified'])->group(function () {
    foreach ([
        'company' => 'Company',
        'attendance' => 'Attendance',
        'employees' => 'Employee',
        'compliance' => 'Compliance',
        'documents' => 'Documents',
        'payroll' => 'Payroll',
        'reports' => 'Reports',
        'configs' => 'Configs',
    ] as $uri => $title) {
        Route::view($uri, 'placeholder', ['title' => $title])->name($uri);
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
