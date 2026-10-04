<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\ComplianceTask;
use App\Models\Employee;
use App\Models\User;
use App\Services\Compliance\ComplianceTasks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_are_logged_with_who_and_which_company(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $company->update(['establishment_name' => 'PI Industries']);
        $company->delete();
        $company->restore();

        $entries = ActivityLog::where('subject_type', $company->getMorphClass())->where('subject_id', $company->id)->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], $entries->pluck('event')->all());
        $this->assertTrue($entries->every(fn ($e) => $e->user_id === $admin->id && $e->company_id === $company->id));
        $this->assertSame(['establishment_name' => 'PI Industry'], $entries[1]->changes['old']);
        $this->assertSame(['establishment_name' => 'PI Industries'], $entries[1]->changes['new']);
    }

    public function test_secret_fields_are_never_written_to_the_log(): void
    {
        $this->actingAs(User::factory()->create());
        $employee = Employee::factory()->create();

        $employee->update(['aadhar_card_no' => '123412341234', 'first_name' => 'Asha']);

        $entry = ActivityLog::where('event', 'updated')->latest('id')->first();
        $this->assertSame('••• hidden', $entry->changes['new']['aadhar_card_no']);
        $this->assertSame('Asha', $entry->changes['new']['first_name']);
        $this->assertStringNotContainsString('123412341234', ActivityLog::query()->pluck('changes')->toJson());
    }

    public function test_sign_ins_and_failed_sign_ins_are_logged(): void
    {
        $user = User::factory()->create(['email' => 'priya@example.com']);

        $this->post('/login', ['email' => 'priya@example.com', 'password' => 'wrong']);
        $this->post('/login', ['email' => 'priya@example.com', 'password' => 'password']);

        $this->assertDatabaseHas('activity_log', ['event' => 'sign-in-failed', 'description' => 'priya@example.com', 'user_id' => $user->id]);
        $this->assertDatabaseHas('activity_log', ['event' => 'signed-in', 'user_id' => $user->id]);
        // The password change from the sign-in itself (remember token, last sign-in) is not noise in the log.
        $this->assertDatabaseMissing('activity_log', ['event' => 'updated', 'subject_id' => $user->id]);
    }

    public function test_system_generated_tasks_are_not_logged_one_by_one(): void
    {
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create();
        Employee::factory()->count(2)->create(['company_id' => $company->id]);

        app(ComplianceTasks::class)->sync($company);

        $this->assertGreaterThan(0, ComplianceTask::count());
        $this->assertSame(0, ActivityLog::where('subject_type', (new ComplianceTask)->getMorphClass())->count());
    }

    public function test_exports_and_bulk_actions_are_logged(): void
    {
        $this->actingAs(User::factory()->create());
        $employee = Employee::factory()->create(['first_name' => 'Ravi', 'last_name' => 'Rao']);

        $this->get(route('company.export'))->assertOk();
        $this->post(route('employees.bulk'), ['ids' => [$employee->uuid], 'action' => 'deactivate']);

        $this->assertDatabaseHas('activity_log', ['event' => 'exported', 'description' => 'companies.csv']);
        $bulk = ActivityLog::where('event', 'bulk')->sole();
        $this->assertSame(['Ravi Rao'], $bulk->changes['employees']);
    }

    public function test_only_admins_see_the_activity_log(): void
    {
        $company = Company::factory()->create(['establishment_name' => 'Logged Ltd']);
        $user = User::factory()->user([$company])->create();

        $this->actingAs($user)->get(route('configs.activity-log'))->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('configs.activity-log', ['event' => 'created']))
            ->assertOk()
            ->assertSee('Activity log')
            ->assertSee('Logged Ltd');
    }
}
