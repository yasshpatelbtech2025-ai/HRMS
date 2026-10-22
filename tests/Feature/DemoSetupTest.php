<?php

namespace Tests\Feature;

use App\Console\Commands\SetupDemo;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-23 12:00:00');
    }

    private function setUpDemo(): User
    {
        $this->artisan('demo:setup')
            ->expectsQuestion('Password for the demo login', 'demo-pass-2026')
            ->assertSuccessful();

        return User::where('email', 'demo@example.com')->sole();
    }

    public function test_it_creates_a_demo_company_with_data_and_a_login_limited_to_it(): void
    {
        $demo = $this->setUpDemo();

        $company = Company::where('establishment_name', SetupDemo::COMPANY)->sole();
        $this->assertSame(24, Employee::where('company_id', $company->id)->count());
        $this->assertSame(24, Payslip::count(), 'September 2026 salary for everyone');

        $this->assertTrue($demo->is_demo);
        $this->assertFalse($demo->isAdmin());
        $this->assertTrue(Hash::check('demo-pass-2026', $demo->password));
        $this->assertSame([$company->id], $demo->companies()->pluck('companies.id')->all());
    }

    public function test_the_demo_login_cannot_see_real_companies(): void
    {
        $real = Company::factory()->create(['establishment_name' => 'Real Client Ltd']);
        $employee = Employee::factory()->create(['company_id' => $real->id, 'first_name' => 'Zubin']);
        $this->actingAs($this->setUpDemo());

        $this->get(route('company.index'))->assertOk()->assertSee(SetupDemo::COMPANY)->assertDontSee('Real Client Ltd');
        $this->get(route('employees.index'))->assertOk()->assertSee('Aarav')->assertDontSee('Zubin');
        $this->get(route('employees.show', $employee))->assertNotFound();
        $this->get(route('company.user-access'))->assertForbidden();
    }

    public function test_the_demo_login_cannot_change_its_profile_or_password(): void
    {
        $demo = $this->setUpDemo();
        $this->actingAs($demo);

        $this->patch(route('profile.update'), ['name' => 'Taken', 'email' => 'mine@example.com'])->assertSessionHas('error');
        $this->put(route('password.update'), ['current_password' => 'demo-pass-2026', 'password' => 'locked-out-1', 'password_confirmation' => 'locked-out-1'])
            ->assertSessionHas('error');

        $demo->refresh();
        $this->assertSame('demo@example.com', $demo->email);
        $this->assertTrue(Hash::check('demo-pass-2026', $demo->password));
    }

    public function test_running_it_again_keeps_the_data_and_only_the_demo_company(): void
    {
        $demo = $this->setUpDemo();
        $demo->companies()->attach(Company::factory()->create());

        $this->setUpDemo();

        $this->assertSame(1, Company::where('establishment_name', SetupDemo::COMPANY)->count());
        $this->assertSame(24, Payslip::count());
        $this->assertSame(1, $demo->companies()->count());
    }

    public function test_it_will_not_turn_a_real_login_into_the_demo(): void
    {
        $admin = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('demo:setup', ['--email' => 'owner@example.com'])->assertFailed();

        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertFalse($admin->fresh()->is_demo);
    }
}
