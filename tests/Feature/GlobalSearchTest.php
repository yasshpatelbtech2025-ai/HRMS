<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_employees_companies_and_pages(): void
    {
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $gopal = Employee::factory()->create(['company_id' => $company->id, 'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0012', 'phone' => '9876501234']);
        Employee::factory()->create(['company_id' => $company->id, 'first_name' => 'Gopal', 'last_name' => 'Shah']);
        Employee::factory()->create(['company_id' => $company->id, 'first_name' => 'Ravi', 'last_name' => 'Bhat', 'separation_date' => '2026-01-01']);

        // Every word must match: "gopal bhat" finds one Gopal, not both.
        $this->getJson(route('search', ['q' => 'gopal bhat']))->assertOk()
            ->assertJsonCount(1, 'employees')->assertJsonPath('employees.0.title', 'Gopal Bhat')
            ->assertJsonPath('employees.0.url', route('employees.show', $gopal))->assertJsonPath('employees.0.initials', 'GB');
        $this->getJson(route('search', ['q' => '0012']))->assertJsonCount(1, 'employees');
        $this->getJson(route('search', ['q' => '98765012']))->assertJsonCount(1, 'employees');
        $bhat = $this->getJson(route('search', ['q' => 'bhat']))->assertJsonCount(2, 'employees');
        $this->assertStringEndsWith('PI Industry · Separated', $bhat->json('employees.1.subtitle'));

        $this->getJson(route('search', ['q' => 'pi ind']))->assertJsonPath('companies.0.title', 'PI Industry')->assertJsonPath('companies.0.subtitle', '3 employees');
        $this->getJson(route('search', ['q' => 'tds']))->assertJsonPath('pages.0.title', 'TDS')->assertJsonPath('pages.0.url', route('payroll.tds'));
        $this->getJson(route('search', ['q' => 'g']))->assertExactJson(['employees' => [], 'companies' => [], 'pages' => []]);
        $this->getJson(route('search', ['q' => '50%_']))->assertJsonCount(0, 'employees');

        $this->get(route('dashboard'))->assertSee('Search employees, companies, pages');
    }

    public function test_search_needs_a_signed_in_user(): void
    {
        $this->getJson(route('search', ['q' => 'gopal']))->assertUnauthorized();
    }
}
