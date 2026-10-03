<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The firm's logo (printed on ID cards) and the ID card design chosen in
 * Company › ID Card Formats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('nearest_police_station');
            $table->unsignedSmallInteger('id_card_format')->default(2);
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'id_card_format']);
        });
    }
};
