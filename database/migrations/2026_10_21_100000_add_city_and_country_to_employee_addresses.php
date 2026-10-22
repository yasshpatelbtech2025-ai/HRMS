<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee addresses now run Address Line 1, Address Line 2, City,
 * Village, Taluka, District, State, Country, like a company's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_addresses', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('country')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('employee_addresses', function (Blueprint $table) {
            $table->dropColumn(['city', 'country']);
        });
    }
};
