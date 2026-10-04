<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the columns lists filter, join and search on. Postgres does
 * not index foreign keys by itself, and every list is filtered by company
 * (company access) and joined by employee. Columns that only record who did
 * something (approved_by, generated_by...) are left unindexed.
 */
return new class extends Migration
{
    /** table => list of column lists */
    private const INDEXES = [
        'attendance_requests' => [['employee_id']],
        'attendances' => [['shift_id']],
        'bonus_entries' => [['employee_id']],
        'companies' => [['parent_company_id']],
        'company_holiday' => [['holiday_id']],
        'compliance_register_entries' => [['employee_id']],
        'compliance_tasks' => [['employee_id'], ['assigned_to']],
        'document_files' => [['company_id']],
        'employee_experiences' => [['employee_id']],
        'employees' => [['company_id', 'status'], ['department_id'], ['sub_department_id'], ['position_id'], ['shift_id'], ['reporting_officer_id']],
        'leave_applications' => [['company_id', 'status'], ['leave_type_id'], ['start_date']],
        'leave_balances' => [['leave_type_id']],
        'loans' => [['company_id'], ['employee_id']],
        'medical_examinations' => [['employee_id']],
        'payslips' => [['company_id', 'month']],
        'salary_adjustments' => [['company_id', 'salary_month']],
        'salary_advances' => [['company_id', 'salary_month'], ['employee_id', 'salary_month']],
        'salary_revisions' => [['company_id'], ['employee_id'], ['status', 'effective_from']],
        'shift_roster_entries' => [['employee_id']],
        'shift_rosters' => [['company_id', 'month']],
        'sub_departments' => [['company_id']],
    ];

    /** Name searches use lower(column) like '%term%'; trigram indexes serve those (Postgres only). */
    private const SEARCHED = [
        'employees' => ['first_name', 'middle_name', 'last_name'],
        'companies' => ['establishment_name'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->index($columns);
                }
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            // Supabase keeps extensions in their own schema; a plain PostgreSQL install has none yet.
            if (! DB::selectOne("select 1 as found from pg_namespace where nspname = 'extensions'")) {
                DB::statement('create schema extensions');
            }
            DB::statement('create extension if not exists pg_trgm with schema extensions');
            foreach (self::SEARCHED as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("create index if not exists {$table}_{$column}_trgm on {$table} using gin (lower({$column}) extensions.gin_trgm_ops)");
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (self::SEARCHED as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("drop index if exists {$table}_{$column}_trgm");
                }
            }
        }

        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->dropIndex($columns);
                }
            });
        }
    }
};
