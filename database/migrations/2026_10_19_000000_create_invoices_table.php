<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice: tax invoices Srympex raises to a client company for a month's
 * manpower supply (admin only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no', 50);
            $table->date('invoice_date');
            $table->date('month')->nullable(); // first day of the month billed
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_gst', 20)->nullable();
            $table->json('lines');       // [{description, amount}] charged the service charge
            $table->json('extra_lines'); // [{description, amount}] added after it
            $table->decimal('service_charge_rate', 5, 2);
            $table->decimal('gst_rate', 5, 2);
            $table->decimal('grand_total', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'invoice_date']);
            $table->index('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
