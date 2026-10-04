<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting these records moves them to Configs › Recently deleted, where
 * they can be restored for 30 days (then they are pruned). Names and codes
 * stay unique among live records only, so a deleted "Helper" position
 * does not block adding a new one.
 */
return new class extends Migration
{
    private const TABLES = [
        'sub_departments', 'positions', 'leave_types', 'holidays', 'leave_applications', 'attendance_requests',
        'loans', 'salary_advances', 'salary_adjustments', 'payroll_rules', 'document_files', 'medical_examinations',
        'compliance_register_entries',
    ];

    /** Unique keys that become partial (live rows only): table => [index name, columns]. */
    private const UNIQUES = [
        'sub_departments' => ['sub_departments_department_id_name_unique', 'department_id, name'],
        'positions' => ['positions_company_id_name_unique', 'company_id, name'],
        'leave_types' => ['leave_types_company_id_code_unique', 'company_id, code'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes()->index();
            });
        }

        foreach (self::UNIQUES as $table => [$index, $columns]) {
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique($index));
            DB::statement("CREATE UNIQUE INDEX {$index} ON {$table} ({$columns}) WHERE deleted_at IS NULL");
        }
    }

    public function down(): void
    {
        foreach (self::UNIQUES as $table => [$index, $columns]) {
            DB::statement("DROP INDEX {$index}");
            Schema::table($table, fn (Blueprint $t) => $t->unique(array_map('trim', explode(',', $columns)), $index));
        }

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
