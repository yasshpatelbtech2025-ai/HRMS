<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company › Firm: the contractor firm running this HRMS - establishment
 * details, its owners and factory manager, stamp / signature images and
 * the mail (SMTP) account it sends email from. One row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firms', function (Blueprint $table) {
            $table->id();
            $table->string('establishment_name');
            $table->string('sector')->nullable();
            $table->string('organization_type')->nullable();
            $table->string('establishment_category')->nullable();
            $table->string('premise_no')->nullable();
            $table->string('pin_code', 10)->nullable();
            $table->date('registered_on')->nullable();
            $table->date('commenced_on')->nullable();
            $table->string('registration_no')->nullable(); // CIN / deed / MSME
            $table->boolean('is_factory_registered')->default(false);
            $table->text('pan')->nullable(); // encrypted
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('village')->nullable();
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('taluka')->nullable();
            $table->string('nearest_police_station')->nullable();
            $table->string('stamp_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('stamp_signature_path')->nullable();
            $table->text('owners')->nullable();          // encrypted JSON list
            $table->text('factory_manager')->nullable(); // encrypted JSON
            $table->string('mail_protocol', 20)->nullable();
            $table->string('mail_host')->nullable();
            $table->unsignedInteger('mail_port')->nullable();
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable(); // encrypted
            $table->string('mail_type', 10)->default('html');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firms');
    }
};
