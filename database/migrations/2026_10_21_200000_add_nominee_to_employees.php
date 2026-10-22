<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The UAN document and the nominee on the employee form's Statutory Info
 * page, alongside the Aadhar, PAN, UAN and ESIC numbers already on the
 * employee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('uan_document_path')->nullable();
            $table->string('nominee_name')->nullable();
            $table->string('nominee_relationship')->nullable();
            $table->string('nominee_document_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['uan_document_path', 'nominee_name', 'nominee_relationship', 'nominee_document_path']);
        });
    }
};
