<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll inputs: salary advances, one-off salary adjustments, loans, and
 * salary revisions (a new salary structure from an effective month).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('released_amount', 12, 2)->default(0); // recovered through salary so far
            $table->date('salary_month'); // first day of the month it is recovered from
            $table->date('advance_date');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('salary_month');
            $table->string('type', 10); // earning, deduction
            $table->timestamps();

            $table->index(['employee_id', 'salary_month']);
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permitted_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('loan_no')->unique();
            $table->text('details')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->unsignedSmallInteger('installment_period'); // months
            $table->decimal('repayment_amount', 12, 2);
            $table->decimal('installment', 12, 2);
            $table->unsignedSmallInteger('installments_cleared')->default(0);
            $table->date('approved_date');
            $table->date('repayment_start'); // first day of the first repayment month
            $table->string('attachment_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_revisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('revised_on');
            $table->date('effective_from'); // first day of the month
            $table->decimal('previous_yearly', 14, 2);
            $table->decimal('revised_yearly', 14, 2);
            $table->json('previous_elements'); // {"basic": {"monthly": .., "yearly": ..}, ...}
            $table->json('revised_elements');
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('pending'); // pending, applied, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_revisions');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('salary_adjustments');
        Schema::dropIfExists('salary_advances');
    }
};
