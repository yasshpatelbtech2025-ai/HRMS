<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('company/export', [CompanyController::class, 'export'])->name('company.export');
    Route::resource('company', CompanyController::class)->only(['index', 'store', 'update', 'destroy']);

    // Every other section tab renders a placeholder page until its module is built.
    foreach (config('navigation') as $section => $tabs) {
        foreach ($tabs as $key => $label) {
            $name = $section.'.'.($key === '' ? 'index' : $key);

            if (! Route::has($name)) {
                Route::view(trim("{$section}/{$key}", '/'), 'placeholder', ['title' => $label, 'section' => $section])->name($name);
            }
        }
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
