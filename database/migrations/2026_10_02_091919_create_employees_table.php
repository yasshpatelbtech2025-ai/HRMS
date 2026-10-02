<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('employee_code');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();

            // Personal
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status', 12)->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Employment
            $table->date('joining_date');
            $table->string('status', 12)->default('active');
            $table->date('separation_date')->nullable();
            $table->string('separation_reason')->nullable();
            $table->decimal('gross_salary', 12, 2)->nullable();

            // Statutory and bank
            $table->string('aadhaar_number', 12)->nullable();
            $table->string('pan_number', 10)->nullable();
            $table->string('uan_number', 12)->nullable();
            $table->string('esic_number', 17)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 34)->nullable();
            $table->string('ifsc_code', 11)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_code']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
