<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the free-text department with links to a company and one of its departments.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['department']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('department');
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('company_id');
            $table->string('department')->nullable()->index();
        });
    }
};
