<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll › Salary Generate: a company's salary sheet for a month and the
 * payslip worked out for each employee on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_sheets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('month'); // first day
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->string('status', 20)->default('not_approved'); // not_approved, approved
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('paid_date')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'month']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('salary_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->string('pay_frequency', 10);
            $table->json('attendance'); // days in month, paid days, P, HD, LOP, WO, PH, leave, COFF, OT hours
            $table->json('earnings');   // [{key, label, amount}]
            $table->json('deductions'); // [{key, label, amount}]
            $table->json('employer');   // employer contributions [{key, label, amount}]
            $table->decimal('gross_earnings', 12, 2);
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->timestamps();

            $table->unique(['salary_sheet_id', 'employee_id']);
            $table->index(['employee_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('salary_sheets');
    }
};
