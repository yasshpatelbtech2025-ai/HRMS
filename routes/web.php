<?php

use App\Http\Controllers\Attendance\CompanyHolidayController;
use App\Http\Controllers\Attendance\HolidayController;
use App\Http\Controllers\Attendance\LeaveTypeController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeparationController;
use App\Http\Controllers\SubDepartmentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('company/export', [CompanyController::class, 'export'])->name('company.export');
    Route::resource('company', CompanyController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::controller(SubDepartmentController::class)->prefix('employees/departments')->group(function () {
        Route::get('/', 'index')->name('employees.departments');
        Route::get('export/{format}', 'export')->name('employees.departments.export');
        Route::post('/', 'store')->name('employees.departments.store');
        Route::put('{subDepartment}', 'update')->name('employees.departments.update');
        Route::delete('{subDepartment}', 'destroy')->name('employees.departments.destroy');
    });

    Route::controller(PositionController::class)->prefix('employees/positions')->group(function () {
        Route::get('/', 'index')->name('employees.positions');
        Route::post('/', 'store')->name('employees.positions.store');
        Route::put('{position}', 'update')->name('employees.positions.update');
        Route::delete('{position}', 'destroy')->name('employees.positions.destroy');
    });

    Route::prefix('employees')->group(function () {
        Route::controller(EmployeeController::class)->group(function () {
            Route::get('/', 'index')->name('employees.index');
            Route::get('create', 'create')->name('employees.create');
            Route::post('/', 'store')->name('employees.store');
            Route::get('export/{format}', 'export')->name('employees.export');
            Route::post('columns', 'toggleColumn')->name('employees.columns');
        });

        Route::post('import', [EmployeeImportController::class, 'store'])->name('employees.import');
        Route::get('import/template/{company}', [EmployeeImportController::class, 'template'])->name('employees.import.template');

        Route::controller(IdCardController::class)->prefix('id-cards')->group(function () {
            Route::get('/', 'index')->name('employees.id-cards');
            Route::post('/', 'store')->name('employees.id-cards.store');
            Route::get('{employee}', 'show')->whereUuid('employee')->name('employees.id-cards.show');
            Route::put('{employee}', 'update')->whereUuid('employee')->name('employees.id-cards.update');
            Route::delete('{employee}', 'destroy')->whereUuid('employee')->name('employees.id-cards.destroy');
            Route::post('{employee}/assign', 'assignToday')->whereUuid('employee')->name('employees.id-cards.assign');
        });

        Route::controller(SeparationController::class)->prefix('separations')->group(function () {
            Route::get('/', 'index')->name('employees.separations');
            Route::post('/', 'store')->name('employees.separations.store');
            Route::put('{employee}', 'update')->whereUuid('employee')->name('employees.separations.update');
            Route::delete('{employee}', 'destroy')->whereUuid('employee')->name('employees.separations.destroy');
        });

        Route::controller(EmployeeController::class)->prefix('{employee}')->whereUuid('employee')->group(function () {
            Route::get('/', 'show')->name('employees.show');
            Route::get('edit', 'edit')->name('employees.edit');
            Route::put('/', 'update')->name('employees.update');
            Route::delete('/', 'destroy')->name('employees.destroy');
            Route::patch('status', 'toggleStatus')->name('employees.status');
            Route::put('separation', 'separate')->name('employees.separation');
            Route::get('files/{file}', 'file')->name('employees.file');
            Route::get('letters/{letter}', 'letter')->name('employees.letter');
        });
    });

    Route::prefix('attendance/leave')->group(function () {
        // Leaves and Leave balance arrive with leave applications.
        Route::view('/', 'attendance.leave.placeholder', ['title' => 'Leaves'])->name('attendance.leave');
        Route::view('balance', 'attendance.leave.placeholder', ['title' => 'Leave balance'])->name('attendance.leave.balance');

        Route::controller(LeaveTypeController::class)->prefix('types')->group(function () {
            Route::get('/', 'index')->name('attendance.leave.types');
            Route::post('/', 'store')->name('attendance.leave.types.store');
            Route::put('{leaveType}', 'update')->name('attendance.leave.types.update');
            Route::delete('{leaveType}', 'destroy')->name('attendance.leave.types.destroy');
        });

        Route::controller(HolidayController::class)->prefix('holiday-list')->group(function () {
            Route::get('/', 'index')->name('attendance.leave.holiday-list');
            Route::post('/', 'store')->name('attendance.leave.holiday-list.store');
            Route::put('{holiday}', 'update')->name('attendance.leave.holiday-list.update');
            Route::delete('{holiday}', 'destroy')->name('attendance.leave.holiday-list.destroy');
        });

        Route::controller(CompanyHolidayController::class)->prefix('holidays')->group(function () {
            Route::get('/', 'index')->name('attendance.leave.holidays');
            Route::post('/', 'store')->name('attendance.leave.holidays.store');
            Route::delete('{company}/{holiday}', 'destroy')->name('attendance.leave.holidays.destroy');
        });
    });

    Route::get('configs', [AttendanceSettingController::class, 'index'])->name('configs.index');
    Route::put('configs/attendance/{company}', [AttendanceSettingController::class, 'update'])->name('configs.attendance.update');

    // Every other section tab renders a placeholder page until its module is built.
    // Names given with ->name() aren't indexed until the lookups are refreshed.
    Route::getRoutes()->refreshNameLookups();

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
