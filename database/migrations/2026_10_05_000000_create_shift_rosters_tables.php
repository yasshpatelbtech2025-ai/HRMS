<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance › Shift management: each department's monthly shift rotation
 * and the resulting shift code (or WO) for every day of each employee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_department_id')->constrained()->cascadeOnDelete();
            $table->date('month'); // first day of the month
            $table->json('parts'); // shift parts rotated through, e.g. ["FS","SS","NS"]
            $table->unsignedTinyInteger('iteration')->default(6); // working days before moving to the next part
            $table->timestamps();

            $table->unique(['sub_department_id', 'month']);
        });

        Schema::create('shift_roster_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_roster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('weekly_off', 10);
            $table->boolean('is_manual')->default(false);
            $table->json('days'); // {"1": "FS", "4": "WO", ...}
            $table->timestamps();

            $table->unique(['shift_roster_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_roster_entries');
        Schema::dropIfExists('shift_rosters');
    }
};
