<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance punches (Attendance › Attendance raw data). Each punch is an
 * in or out time, assigned to the attendance day it counts towards — a
 * night shift's out punch the next morning belongs to the evening before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->dateTime('punched_at');
            $table->date('work_date');
            $table->string('type', 3); // in, out
            $table->string('source', 20)->default('manual'); // manual, smart, request, device
            $table->string('device')->nullable();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete(); // the employee's shift when punched
            $table->string('shift_code', 4)->nullable(); // the part actually worked: GS, DS, FS, SS, NS
            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_missing')->default(false); // no matching in/out
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'work_date']);
            $table->index(['company_id', 'work_date']);
            $table->index(['employee_id', 'punched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
