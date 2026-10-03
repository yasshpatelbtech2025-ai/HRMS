<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll › TDS: a monthly TDS amount set by hand for an employee for a
 * financial year, used instead of the estimate.
 *
 * Payroll › Bonus: a company's yearly bonus sheet for an accounting year
 * (April - March) and each employee's bonus on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tds_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fy'); // starting year: 2026 = April 2026 - March 2027
            $table->decimal('monthly_amount', 12, 2);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'fy']);
        });

        Schema::create('bonus_sheets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fy');
            $table->string('status', 20)->default('draft'); // draft, approved
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('paid_on')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'fy']);
        });

        Schema::create('bonus_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonus_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('days_worked', 6, 1);
            $table->decimal('salary', 12, 2);        // basic + DA earned in the year
            $table->decimal('bonus_salary', 12, 2);  // the same, capped at the calculation ceiling per month
            $table->decimal('rate', 5, 2);
            $table->boolean('eligible');
            $table->string('reason')->nullable();    // why not eligible
            $table->decimal('calculated', 12, 2);
            $table->decimal('amount', 12, 2);        // what is paid (editable while draft)
            $table->timestamps();

            $table->unique(['bonus_sheet_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_entries');
        Schema::dropIfExists('bonus_sheets');
        Schema::dropIfExists('tds_overrides');
    }
};
