<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compliance › Forms: rows typed into registers (Form C, 20, 21), and the
 * fill-in fields and signed copies of returns (Form 25, Bonus Form D,
 * Gratuity Form F) for a company and period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_register_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('form', 40);
            $table->date('month');
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->timestamps();

            $table->index(['company_id', 'form', 'month']);
        });

        Schema::create('compliance_form_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('form', 40);
            $table->string('period', 20); // e.g. 2026-06, 2025-2026, or "employee:<id>"
            $table->json('data')->nullable();
            $table->string('signed_copy_path')->nullable();
            $table->string('signed_copy_name')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'form', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_form_values');
        Schema::dropIfExists('compliance_register_entries');
    }
};
