<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance foundations from the old HRMS's Configs › Attendance and
 * Attendance › Leave tabs: per-company shift definitions with their pay
 * rules, the punch buffer, leave types and holidays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('punch_buffer_minutes')->default(30);
            $table->timestamps();
        });

        // Shifts become the company's configured shifts: one Open shift, up to
        // four General shifts, one Day & Night shift and up to four Rotational
        // shifts. "timings" holds each part's start and end, keyed by its code
        // (GS; DS/NS; FS/SS/NS).
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('type')->default('general');
            $table->unsignedTinyInteger('slot')->default(1);
            $table->json('timings')->nullable();
            $table->string('half_day_rule')->default('slab');
            $table->string('wo_ph_work_pay')->default('coff');
            $table->boolean('pay_daily_rate_on_wo_ph')->default(false);
            $table->string('ot_effect_on')->default('basic');
            $table->string('ot_rate')->default('regular');
            $table->string('ph_ot_effect_on')->default('basic');
            $table->string('ph_ot_rate')->default('regular');
            $table->decimal('ot_after_hours', 5, 2)->nullable();
            $table->unsignedSmallInteger('late_penalty_after_minutes')->nullable();
            $table->string('pf_effect_on')->default('basic');
            $table->string('esi_effect_on')->default('gross');

            $table->unique(['company_id', 'type', 'slot']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->decimal('days', 5, 1)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // The master holiday list; companies pick from it on the Holiday tab.
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index('start_date');
        });

        Schema::create('company_holiday', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('holiday_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'holiday_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_holiday');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_types');

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'type', 'slot']);
            $table->dropColumn([
                'type', 'slot', 'timings', 'half_day_rule', 'wo_ph_work_pay', 'pay_daily_rate_on_wo_ph', 'ot_effect_on', 'ot_rate',
                'ph_ot_effect_on', 'ph_ot_rate', 'ot_after_hours', 'late_penalty_after_minutes', 'pf_effect_on', 'esi_effect_on',
            ]);
        });

        Schema::dropIfExists('attendance_settings');
    }
};
