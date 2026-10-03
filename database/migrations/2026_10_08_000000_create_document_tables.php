<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents page: files uploaded to each document slot, and who each slot
 * is assigned to. A slot is identified by its slug, e.g.
 * "agreement-105-pi-industry-agreement".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slot');
            $table->string('category');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('slot');
        });

        Schema::create('document_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('slot')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_assignments');
        Schema::dropIfExists('document_files');
    }
};
