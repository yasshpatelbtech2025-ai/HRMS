<?php

use App\Http\Controllers\Attendance\AttendanceRequestController;
use App\Http\Controllers\Attendance\CompanyHolidayController;
use App\Http\Controllers\Attendance\EmployeeReportController;
use App\Http\Controllers\Attendance\HolidayController;
use App\Http\Controllers\Attendance\LeaveBalanceController;
use App\Http\Controllers\Attendance\LeaveController;
use App\Http\Controllers\Attendance\LeaveTypeController;
use App\Http\Controllers\Attendance\MissingPunchController;
use App\Http\Controllers\Attendance\MonthlyAttendanceController;
use App\Http\Controllers\Attendance\RawDataController;
use App\Http\Controllers\Attendance\ShiftManagementController;
use App\Http\Controllers\Attendance\SmartAttendanceController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FirmController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\RecentlyDeletedController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserAccessController;
use App\Http\Controllers\IdCardFormatController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ComplianceTaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\Payroll\BonusController;
use App\Http\Controllers\Payroll\CurrentSalaryController;
use App\Http\Controllers\Payroll\LoanController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\Payroll\SalaryAdjustmentController;
use App\Http\Controllers\Payroll\SalaryAdvanceController;
use App\Http\Controllers\Payroll\SalaryGenerateController;
use App\Http\Controllers\Payroll\SalaryRevisionController;
use App\Http\Controllers\Payroll\TdsController;
use App\Http\Controllers\PayrollSettingController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SeparationController;
use App\Http\Controllers\SubDepartmentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified', 'throttle:app'])->name('dashboard');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/context/companies', [\App\Http\Controllers\CompanyContextController::class, 'index'])->name('context.companies');
    Route::post('/context/company', [\App\Http\Controllers\CompanyContextController::class, 'update'])->name('context.company');
});
Route::get('/search', SearchController::class)->middleware(['auth', 'verified', 'throttle:60,1'])->name('search');

Route::middleware(['auth', 'verified', 'throttle:app'])->group(function () {
    Route::get('company/export', [CompanyController::class, 'export'])->name('company.export');
    // Before the company resource, so "firm" is not taken for a company.
    Route::controller(FirmController::class)->prefix('company/firm')->group(function () {
        Route::get('/', 'show')->name('company.firm');
        Route::put('/', 'update')->name('company.firm.update');
        Route::get('image/{image}', 'image')->name('company.firm.image');
        Route::post('test-mail', 'testMail')->name('company.firm.test-mail');
    });
    Route::get('company/id-card-formats', [IdCardFormatController::class, 'index'])->name('company.id-card-formats');
    Route::post('company/id-card-formats', [IdCardFormatController::class, 'select'])->name('company.id-card-formats.select');
    Route::controller(UserAccessController::class)->prefix('company/user-access')->group(function () {
        Route::get('/', 'index')->name('company.user-access');
        Route::post('/', 'store')->name('company.user-access.store');
        Route::put('{user}', 'update')->name('company.user-access.update');
    });
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
            Route::post('bulk', 'bulk')->name('employees.bulk');
            Route::post('columns', 'toggleColumn')->name('employees.columns');
        });

        Route::post('import', [EmployeeImportController::class, 'store'])->name('employees.import');
        Route::get('import/template/{company}', [EmployeeImportController::class, 'template'])->name('employees.import.template');
        Route::get('import/{run}', [EmployeeImportController::class, 'status'])->name('employees.import.status');

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

    Route::controller(MonthlyAttendanceController::class)->prefix('attendance')->group(function () {
        Route::get('/', 'index')->name('attendance.index');
        Route::post('monthly/time', 'setTime')->name('attendance.monthly.time');
        Route::post('monthly/leave', 'setLeave')->name('attendance.monthly.leave');
        Route::post('monthly/coff', 'useCompOff')->name('attendance.monthly.coff');
        Route::post('monthly/reset', 'reset')->name('attendance.monthly.reset');
    });
    Route::controller(AttendanceRequestController::class)->prefix('attendance/requests')->group(function () {
        Route::get('/', 'index')->name('attendance.requests');
        Route::post('/', 'store')->name('attendance.requests.store');
        Route::put('{attendanceRequest}/approve', 'approve')->name('attendance.requests.approve');
        Route::put('{attendanceRequest}/reject', 'reject')->name('attendance.requests.reject');
        Route::delete('{attendanceRequest}', 'destroy')->name('attendance.requests.destroy');
    });
    Route::get('attendance/missing-punch', [MissingPunchController::class, 'index'])->name('attendance.missing-punch');
    Route::get('attendance/shifts', [ShiftManagementController::class, 'index'])->name('attendance.shifts');
    Route::post('attendance/shifts/{company}', [ShiftManagementController::class, 'store'])->name('attendance.shifts.store');
    Route::get('attendance/employee-report', [EmployeeReportController::class, 'index'])->name('attendance.employee-report');

    Route::controller(RawDataController::class)->prefix('attendance/raw-data')->group(function () {
        Route::get('/', 'index')->name('attendance.raw-data');
        Route::post('/', 'store')->name('attendance.raw-data.store');
        Route::post('correct', 'correct')->name('attendance.raw-data.correct');
        Route::get('export/{format}', 'export')->name('attendance.raw-data.export');
        Route::patch('{attendance}', 'update')->name('attendance.raw-data.update');
        Route::delete('{attendance}', 'destroy')->name('attendance.raw-data.destroy');
        Route::patch('{attendance}/lock', 'toggleLock')->name('attendance.raw-data.lock');
    });

    Route::get('attendance/smart', [SmartAttendanceController::class, 'index'])->name('attendance.smart');
    Route::post('attendance/smart', [SmartAttendanceController::class, 'store'])->name('attendance.smart.store');

    Route::prefix('attendance/leave')->group(function () {
        Route::controller(LeaveController::class)->group(function () {
            Route::get('/', 'index')->name('attendance.leave');
            Route::post('/', 'store')->name('attendance.leave.store');
            Route::get('export/{format}', 'export')->name('attendance.leave.export');
            Route::put('{leave}/approve', 'approve')->whereUuid('leave')->name('attendance.leave.approve');
            Route::put('{leave}/reject', 'reject')->whereUuid('leave')->name('attendance.leave.reject');
            Route::delete('{leave}', 'destroy')->whereUuid('leave')->name('attendance.leave.destroy');
            Route::get('{leave}/hard-copy', 'hardCopy')->whereUuid('leave')->name('attendance.leave.hard-copy');
        });

        Route::controller(LeaveBalanceController::class)->prefix('balance')->group(function () {
            Route::get('/', 'index')->name('attendance.leave.balance');
            Route::get('export', 'export')->name('attendance.leave.balance.export');
            Route::put('{employee}/{leaveType}', 'update')->name('attendance.leave.balance.update');
        });

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

    Route::prefix('payroll')->group(function () {
        Route::controller(SalaryGenerateController::class)->group(function () {
            Route::get('/', 'index')->name('payroll.index');
            Route::post('/', 'store')->name('payroll.generate');
            Route::get('approval/{sheet}', 'approval')->name('payroll.approval');
            Route::get('chart/{sheet}', 'chartPage')->name('payroll.chart');
            Route::get('chart/{sheet}/export', 'export')->name('payroll.chart.export');
            Route::get('export/{format}', 'exportList')->name('payroll.export');
            Route::put('approval/{sheet}', 'approve')->name('payroll.approve');
            Route::put('approval/{sheet}/undo', 'unapprove')->name('payroll.unapprove');
            Route::put('sheets/{sheet}/paid-date', 'paidDate')->name('payroll.paid-date');
            Route::delete('sheets/{sheet}', 'destroy')->name('payroll.destroy');
        });

        Route::controller(PayslipController::class)->group(function () {
            Route::get('salaries', 'index')->name('payroll.salaries');
            Route::get('salaries/export/{format}', 'export')->name('payroll.salaries.export');
            Route::get('payslips/{payslip}', 'show')->name('payroll.payslips.show');
            Route::put('payslips/{payslip}/regenerate', 'regenerate')->name('payroll.payslips.regenerate');
        });

        Route::controller(SalaryAdvanceController::class)->prefix('advances')->group(function () {
            Route::get('/', 'index')->name('payroll.advances');
            Route::post('/', 'store')->name('payroll.advances.store');
            Route::get('export/{format}', 'export')->name('payroll.advances.export');
            Route::put('{advance}', 'update')->name('payroll.advances.update');
            Route::delete('{advance}', 'destroy')->name('payroll.advances.destroy');
            Route::get('{advance}/attachment', 'attachment')->name('payroll.advances.attachment');
        });

        Route::controller(SalaryAdjustmentController::class)->prefix('adjustments')->group(function () {
            Route::get('/', 'index')->name('payroll.adjustments');
            Route::post('/', 'store')->name('payroll.adjustments.store');
            Route::get('export/{format}', 'export')->name('payroll.adjustments.export');
            Route::put('{adjustment}', 'update')->name('payroll.adjustments.update');
            Route::delete('{adjustment}', 'destroy')->name('payroll.adjustments.destroy');
        });

        Route::controller(LoanController::class)->prefix('loans')->group(function () {
            Route::get('/', 'index')->name('payroll.loans');
            Route::post('/', 'store')->name('payroll.loans.store');
            Route::get('export/{format}', 'export')->name('payroll.loans.export');
            Route::put('{loan}', 'update')->name('payroll.loans.update');
            Route::delete('{loan}', 'destroy')->name('payroll.loans.destroy');
            Route::get('{loan}/attachment', 'attachment')->name('payroll.loans.attachment');
        });

        Route::controller(SalaryRevisionController::class)->prefix('revisions')->group(function () {
            Route::get('/', 'index')->name('payroll.revisions');
            Route::get('export/{format}', 'export')->name('payroll.revisions.export');
            Route::get('create/{employee}', 'create')->name('payroll.revisions.create');
            Route::post('create/{employee}', 'store')->name('payroll.revisions.store');
            Route::put('{revision}/apply', 'apply')->name('payroll.revisions.apply');
            Route::put('{revision}/cancel', 'cancel')->name('payroll.revisions.cancel');
        });

        Route::get('current', [CurrentSalaryController::class, 'index'])->name('payroll.current');
        Route::get('current/export/{format}', [CurrentSalaryController::class, 'export'])->name('payroll.current.export');

        Route::controller(TdsController::class)->prefix('tds')->group(function () {
            Route::get('/', 'index')->name('payroll.tds');
            Route::get('export/{format}', 'export')->whereIn('format', ['csv', 'xlsx'])->name('payroll.tds.export');
            Route::post('{employee}', 'setMonthly')->name('payroll.tds.override');
        });

        Route::controller(BonusController::class)->prefix('bonus')->group(function () {
            Route::get('/', 'index')->name('payroll.bonus');
            Route::post('/', 'generate')->name('payroll.bonus.generate');
            Route::put('{sheet}', 'update')->name('payroll.bonus.update');
            Route::post('{sheet}/approve', 'approve')->name('payroll.bonus.approve');
            Route::post('{sheet}/unapprove', 'unapprove')->name('payroll.bonus.unapprove');
            Route::delete('{sheet}', 'destroy')->name('payroll.bonus.destroy');
            Route::get('{sheet}/export/{format}', 'export')->whereIn('format', ['csv', 'xlsx'])->name('payroll.bonus.export');
        });
    });

    Route::controller(DocumentController::class)->prefix('documents')->group(function () {
        Route::get('/', 'index')->name('documents.index');
        Route::get('files/{file}', 'file')->name('documents.file');
        Route::delete('files/{file}', 'destroy')->name('documents.destroy');
        Route::get('{slot}', 'show')->name('documents.show');
        Route::post('{slot}/upload', 'upload')->name('documents.upload');
        Route::put('{slot}/assign', 'assign')->name('documents.assign');
    });

    Route::controller(ComplianceTaskController::class)->prefix('compliance/to-do')->group(function () {
        Route::get('/', 'index')->name('compliance.to-do');
        Route::post('/', 'store')->name('compliance.tasks.store');
        Route::post('bulk', 'bulk')->name('compliance.tasks.bulk');
        Route::patch('{task}', 'update')->name('compliance.tasks.update');
        Route::post('{task}/complete', 'complete')->name('compliance.tasks.complete');
        Route::post('{task}/reopen', 'reopen')->name('compliance.tasks.reopen');
        Route::delete('{task}', 'destroy')->name('compliance.tasks.destroy');
    });

    Route::controller(ComplianceController::class)->prefix('compliance')->group(function () {
        Route::get('/', 'index')->name('compliance.index');
        Route::get('forms/{company}/{form}', 'show')->name('compliance.forms.show');
        Route::post('forms/{company}/{form}/entries', 'storeEntries')->name('compliance.forms.entries');
        Route::post('forms/{company}/{form}/values', 'storeValues')->name('compliance.forms.values');
        Route::get('forms/{company}/{form}/signed-copy/{period}', 'signedCopy')->name('compliance.forms.signed-copy');
        Route::delete('entries/{entry}', 'destroyEntry')->name('compliance.entries.destroy');
        Route::post('forms/{company}/examinations', 'storeExamination')->name('compliance.examinations.store');
        Route::put('examinations/{examination}', 'updateExamination')->name('compliance.examinations.update');
        Route::delete('examinations/{examination}', 'destroyExamination')->name('compliance.examinations.destroy');
        Route::get('examinations/{examination}/signed-copy', 'examinationCopy')->name('compliance.examinations.signed-copy');
    });

    Route::get('reports', [ReportController::class, 'sheet'])->name('reports.index');
    foreach (['attendance', 'lateness', 'attendance-log', 'daily-present', 'employees'] as $report) {
        Route::get("reports/{$report}", [ReportController::class, 'show'])->defaults('report', $report)->name("reports.{$report}");
    }

    Route::get('configs', [AttendanceSettingController::class, 'index'])->name('configs.index');
    Route::get('configs/activity-log', ActivityLogController::class)->name('configs.activity-log');
    Route::controller(RecentlyDeletedController::class)->prefix('configs/recently-deleted')->group(function () {
        Route::get('/', 'index')->name('configs.recently-deleted');
        Route::post('{type}/restore', 'restoreMany')->name('recently-deleted.restore-many');
        Route::post('{type}/{id}', 'restore')->whereNumber('id')->name('recently-deleted.restore');
        Route::delete('{type}/{id}', 'destroy')->whereNumber('id')->name('recently-deleted.destroy');
    });
    Route::put('configs/attendance/{company}', [AttendanceSettingController::class, 'update'])->name('configs.attendance.update');

    Route::controller(PayrollSettingController::class)->prefix('configs/payroll')->group(function () {
        Route::get('/', 'index')->name('configs.payroll');
        Route::put('{company}', 'update')->whereUuid('company')->name('configs.payroll.update');
        Route::post('rules', 'storeRule')->name('configs.payroll.rules.store');
        Route::put('rules/{rule}', 'updateRule')->name('configs.payroll.rules.update');
        Route::delete('rules/{rule}', 'destroyRule')->name('configs.payroll.rules.destroy');
    });

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
