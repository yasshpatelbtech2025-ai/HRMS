<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payable days entered at Salary Generate for employees on the "Fixed
 * payable days" monthly salary calculation, by employee id, so a payslip
 * can be regenerated with the same days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_sheets', function (Blueprint $table) {
            $table->json('payable_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('salary_sheets', function (Blueprint $table) {
            $table->dropColumn('payable_days');
        });
    }
};
