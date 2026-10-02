<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the employee record up to the old HRMS's "Add employee" form:
 * basic info, employment details, documents, bank, medical, salary info,
 * plus addresses, qualifications, experience, family and salary elements
 * in their own tables. Aadhaar, PAN, passport, licence and account
 * numbers are stored encrypted (text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // Employee ids are numbered per company ("0001"), so they are only unique within one.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['employee_code']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['designation', 'address']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
            $table->unique(['company_id', 'employee_code']);

            // Basic info
            $table->string('middle_name')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->unsignedSmallInteger('no_of_kids')->nullable();
            $table->string('country')->default('India');

            // Employee details
            $table->foreignId('sub_department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporting_officer_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('works_on_hazardous_process')->default(false);
            $table->boolean('can_punch_in_hybrid_mode')->default(false);
            $table->string('working_place')->nullable();
            $table->boolean('permanent_address_is_present')->default(false);

            // Documents
            $table->text('aadhar_card_no')->nullable();
            $table->text('pan_card_no')->nullable();
            $table->text('passport_no')->nullable();
            $table->text('driving_license_no')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('aadhar_attachment_path')->nullable();
            $table->string('pan_attachment_path')->nullable();

            // Bank
            $table->string('bank_name')->nullable();
            $table->string('branch_address')->nullable();
            $table->text('account_number')->nullable();
            $table->string('bank_passbook_path')->nullable();
            $table->string('micr_code')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('uan_code')->nullable();
            $table->string('name_as_per_uan')->nullable();
            $table->string('ip_number')->nullable();
            $table->string('name_as_per_esic')->nullable();

            // Medical
            $table->string('blood_group')->nullable()->index();
            $table->string('health_condition')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->string('disability_description')->nullable();

            // Qualification / experience
            $table->string('job_status')->nullable();
            $table->date('date_of_exit_prev_org')->nullable();

            // Salary info
            $table->string('pay_frequency')->default('monthly');
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->decimal('daily_rate', 12, 2)->nullable();
            $table->decimal('daily_hour', 5, 2)->nullable();
            $table->string('tax_regime')->default('new');
            $table->string('monthly_salary_calculation')->nullable();
            $table->string('weekly_leave_day')->nullable();

            // Attendance / lifecycle
            $table->string('nfc_card_no')->nullable();
            $table->date('separation_date')->nullable()->index();
            $table->date('id_card_assigned_on')->nullable();
        });

        Schema::create('employee_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // present, permanent, nominee_permanent, nominee_current
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('taluka')->nullable();
            $table->string('state')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'type']);
        });

        Schema::create('employee_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('level'); // 10th, 12th, graduate, post_graduate, technical
            $table->string('school')->nullable();
            $table->string('tenure')->nullable();
            $table->string('passing_year')->nullable();
            $table->string('percentage')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'level']);
        });

        Schema::create('employee_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('salary')->nullable();
            $table->string('total_years_worked')->nullable();
            $table->string('reason_of_leaving')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('relation'); // mother, father, spouse, child
            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('aadhar_card_no')->nullable();
            $table->boolean('is_nominee')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'relation']);
        });

        Schema::create('employee_salary_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('element'); // basic, da, hra, ..., vpf, notice_period_recovery
            $table->decimal('monthly', 12, 2)->default(0);
            $table->decimal('yearly', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'element']);
        });

        // Per-user list preferences, e.g. which optional Employee list columns are shown.
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferences');
        });

        Schema::dropIfExists('employee_salary_elements');
        Schema::dropIfExists('employee_family_members');
        Schema::dropIfExists('employee_experiences');
        Schema::dropIfExists('employee_qualifications');
        Schema::dropIfExists('employee_addresses');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['sub_department_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['reporting_officer_id']);
            $table->dropUnique(['company_id', 'employee_code']);
            $table->dropUnique(['uuid']);
            $table->dropIndex(['blood_group']);
            $table->dropIndex(['separation_date']);
            $table->dropColumn([
                'uuid', 'middle_name', 'alternate_phone', 'marital_status', 'religion', 'caste', 'no_of_kids', 'country',
                'sub_department_id', 'position_id', 'shift_id', 'reporting_officer_id', 'works_on_hazardous_process',
                'can_punch_in_hybrid_mode', 'working_place', 'permanent_address_is_present', 'aadhar_card_no', 'pan_card_no',
                'passport_no', 'driving_license_no', 'photo_path', 'aadhar_attachment_path', 'pan_attachment_path',
                'bank_name', 'branch_address', 'account_number', 'bank_passbook_path', 'micr_code', 'ifsc_code', 'uan_code',
                'name_as_per_uan', 'ip_number', 'name_as_per_esic', 'blood_group', 'health_condition', 'is_disabled',
                'disability_description', 'job_status', 'date_of_exit_prev_org', 'pay_frequency', 'hourly_rate', 'daily_rate',
                'daily_hour', 'tax_regime', 'monthly_salary_calculation', 'weekly_leave_day', 'nfc_card_no',
                'separation_date', 'id_card_assigned_on',
            ]);
            $table->string('designation')->nullable();
            $table->text('address')->nullable();
            $table->unique('employee_code');
        });

        Schema::dropIfExists('shifts');
    }
};
