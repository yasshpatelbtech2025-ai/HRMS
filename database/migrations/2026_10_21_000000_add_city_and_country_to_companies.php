<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A company's address now runs Address Line 1, Address Line 2, City,
 * Village, Taluka, District, State, Country.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('country')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['city', 'country']);
        });
    }
};
