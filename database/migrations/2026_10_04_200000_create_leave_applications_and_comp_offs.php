<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave applications (Attendance › Leave › Leaves, or "Enter Leave" on the
 * monthly attendance grid) and compensatory-off days taken (COFF).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('hard_copy_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->date('approved_start_date')->nullable();
            $table->date('approved_end_date')->nullable();
            $table->decimal('approved_days', 5, 1)->nullable();
            $table->text('approval_description')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status', 'approved_start_date']);
        });

        Schema::create('comp_off_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_off_uses');
        Schema::dropIfExists('leave_applications');
    }
};
