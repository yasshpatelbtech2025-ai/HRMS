<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee Excel imports run in the background; each upload's progress and
 * outcome is kept here and shown on the employee list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('path')->nullable();
            $table->string('status', 20)->default('queued'); // queued, running, done, invalid, failed
            $table->unsignedInteger('imported')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_imports');
    }
};
