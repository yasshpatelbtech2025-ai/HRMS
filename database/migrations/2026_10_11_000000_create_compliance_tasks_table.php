<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compliance › To Do List: what is due for each company - per new employee
 * (fitness certificate, I-card, police verification), monthly (salary, PF,
 * ESIC, PT), half-yearly (health register) - plus tasks added by hand.
 * Generated tasks are unique per company, type, employee and period so they
 * are created once; deleted ones are soft-deleted so they stay away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40); // a key of config('compliance.tasks'), or "custom"
            $table->foreignId('employee_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('period', 10)->default(''); // 2026-09, 2026-H1, or '' for once-only tasks
            $table->string('title');
            $table->string('category', 40);
            $table->string('department', 40);
            $table->date('due_on');
            $table->string('priority', 10)->default('high');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete(); // null when completed automatically
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'type', 'employee_id', 'period']);
            $table->index(['company_id', 'completed_at', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_tasks');
    }
};
