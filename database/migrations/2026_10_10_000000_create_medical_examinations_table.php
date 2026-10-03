<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Factory Act: an employee's medical examinations by the certifying
 * surgeon. One examination fills Form 5, 27-A, 30 and the Form 32 health
 * register; the less common fields live in `details`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_examinations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('examined_on');
            $table->string('result', 10)->nullable(); // fit / unfit
            $table->string('certifying_surgeon')->nullable();
            $table->text('signs_symptoms')->nullable();
            $table->date('extended_until')->nullable();
            $table->json('details')->nullable();
            $table->string('signed_copy_path')->nullable();
            $table->string('signed_copy_name')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'examined_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_examinations');
    }
};
