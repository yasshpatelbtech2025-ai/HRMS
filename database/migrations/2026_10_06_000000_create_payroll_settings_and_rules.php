<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configs › Payroll: each company's payroll settings, and payroll rules
 * such as a variable bonus for a month.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('probation_period_months')->nullable();
            $table->unsignedSmallInteger('notice_period_days')->nullable();
            $table->string('salary_structure', 10)->default('old'); // old, new
            $table->string('bonus_frequency', 10)->default('yearly'); // yearly, monthly
            $table->boolean('tax_deduction')->default(false);
            $table->boolean('lwf_paid_by_company')->default(false);
            $table->string('pf_effect_on', 10)->default('basic');
            $table->string('esi_effect_on', 10)->default('gross');
            $table->timestamps();
        });

        Schema::create('payroll_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('variable_bonus');
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->date('month'); // first day of the month it applies to
            $table->boolean('is_percent')->default(false);
            $table->string('effect_on', 10)->default('basic'); // basic, gross — for percentages
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_rules');
        Schema::dropIfExists('payroll_settings');
    }
};
