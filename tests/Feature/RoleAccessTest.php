<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $mine;

    private Company $theirs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mine = Company::factory()->create(['establishment_name' => 'Mine Ltd']);
        $this->theirs = Company::factory()->create(['establishment_name' => 'Theirs Ltd']);
    }

    private function actAsUser(): User
    {
        $user = User::factory()->user([$this->mine])->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_a_user_sees_and_opens_only_their_companies_records(): void
    {
        $own = Employee::factory()->create(['company_id' => $this->mine->id, 'first_name' => 'Asha']);
        $other = Employee::factory()->create(['company_id' => $this->theirs->id, 'first_name' => 'Zubin']);
        $this->actAsUser();

        $this->get(route('company.index'))->assertOk()->assertSee('Mine Ltd')->assertDontSee('Theirs Ltd');
        $this->get(route('employees.index'))->assertOk()->assertSee('Asha')->assertDontSee('Zubin');
        $this->get(route('employees.show', $own))->assertOk();
        $this->get(route('employees.show', $other))->assertNotFound();
    }

    public function test_a_user_cannot_write_to_another_companys_records(): void
    {
        $other = Employee::factory()->create(['company_id' => $this->theirs->id]);
        $this->actAsUser();

        $this->post(route('employees.store'), ['company_id' => $this->theirs->id])->assertSessionHasErrors('company_id');
        $this->post(route('employees.bulk'), ['ids' => [$other->uuid], 'action' => 'deactivate']);
        $this->assertSame($other->status, $other->fresh()->status);

        // Even a direct model write is refused.
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        Employee::withoutGlobalScopes()->find($other->id)->update(['first_name' => 'Changed']);
    }

    public function test_users_cannot_delete_and_do_not_see_delete_buttons(): void
    {
        $employee = Employee::factory()->create(['company_id' => $this->mine->id]);
        $this->actAsUser();

        $this->get(route('employees.index'))->assertOk()->assertDontSee('Delete :count selected employee', false);
        $this->delete(route('employees.destroy', $employee))->assertRedirect()->assertSessionHas('error', 'Only admins can delete. Ask an admin if something needs removing.');
        $this->post(route('employees.bulk'), ['ids' => [$employee->uuid], 'action' => 'delete'])->assertSessionHas('error');
        $this->assertNotSoftDeleted($employee);
    }

    public function test_users_cannot_open_admin_only_pages_and_do_not_see_their_tabs(): void
    {
        $this->actAsUser();

        $this->get(route('company.user-access'))->assertForbidden();
        $this->get(route('company.firm'))->assertForbidden();
        $this->post(route('company.store'), ['establishment_name' => 'New'])->assertForbidden();
        $this->get(route('company.index'))->assertDontSee('User access')->assertDontSee('ID Card Formats')->assertDontSee('Add company');
    }

    public function test_inactive_accounts_cannot_sign_in_and_are_signed_out(): void
    {
        $user = User::factory()->create(['email' => 'off@example.com']);
        $user->forceFill(['is_active' => false])->save();

        $this->post('/login', ['email' => 'off@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This account has been deactivated. Ask an admin.']);
        $this->assertGuest();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sign_in_records_the_last_login(): void
    {
        $user = User::factory()->create(['email' => 'on@example.com']);

        $this->post('/login', ['email' => 'on@example.com', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_admin_adds_a_user_with_companies(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('company.user-access'))->assertOk()->assertSee('1 of 2 admin seats in use');

        $this->post(route('company.user-access.store'), [
            'name' => 'Priya', 'email' => 'priya@example.com', 'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
            'role' => 'user', 'is_active' => '1', 'companies' => [$this->mine->id],
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'priya@example.com')->first();
        $this->assertSame('user', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertEquals([$this->mine->id], $user->companies->modelKeys());
    }

    public function test_a_user_needs_at_least_one_company(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('company.user-access.store'), [
            'name' => 'Priya', 'email' => 'priya@example.com', 'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
            'role' => 'user', 'is_active' => '1',
        ])->assertSessionHasErrors('companies');
    }

    public function test_seat_caps_are_enforced_for_active_accounts(): void
    {
        $admin = User::factory()->create();
        User::factory()->create();
        User::factory()->user([$this->mine])->count(10)->create();
        $this->actingAs($admin);

        $add = fn (string $role, bool $active) => $this->post(route('company.user-access.store'), [
            'name' => 'Extra', 'email' => "extra-{$role}-".(int) $active.'@example.com', 'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
            'role' => $role, 'is_active' => $active ? '1' : '0', 'companies' => [$this->mine->id],
        ]);

        $add('admin', true)->assertSessionHasErrors(['role' => 'All 2 admin seats are taken. Deactivate a login first.']);
        $add('user', true)->assertSessionHasErrors('role');
        // An inactive account takes no seat.
        $add('user', false)->assertSessionHasNoErrors();
    }

    public function test_an_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->put(route('company.user-access.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'user', 'is_active' => '1', 'companies' => [$this->mine->id],
        ])->assertSessionHasErrors('role');
        $this->put(route('company.user-access.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'is_active' => '0',
        ])->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_reassigning_companies_changes_what_a_user_sees(): void
    {
        $user = User::factory()->user([$this->mine])->create();
        $this->actingAs(User::factory()->create());

        $this->put(route('company.user-access.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'user', 'is_active' => '1', 'companies' => [$this->theirs->id],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user->fresh());
        \App\Support\CompanyAccess::forget();
        $this->get(route('company.index'))->assertSee('Theirs Ltd')->assertDontSee('Mine Ltd');
    }
}
