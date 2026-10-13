<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_login(): void
    {
        $this->getJson(route('search', ['q' => 'ab']))->assertUnauthorized();
    }

    public function test_finds_employees_and_companies(): void
    {
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create(['establishment_name' => 'Ravindra Textiles']);
        Employee::factory()->for($company)->create(['first_name' => 'Ravi', 'last_name' => 'Shah']);
        Employee::factory()->for($company)->create(['first_name' => 'Meena']);

        $this->getJson(route('search', ['q' => 'rav']))
            ->assertOk()
            ->assertJsonCount(1, 'employees')
            ->assertJsonPath('employees.0.label', 'Ravi Shah')
            ->assertJsonPath('companies.0.label', 'Ravindra Textiles');
    }

    public function test_short_queries_return_nothing(): void
    {
        $this->actingAs(User::factory()->create());
        Employee::factory()->create(['first_name' => 'A']);

        $this->getJson(route('search', ['q' => 'a']))->assertExactJson(['employees' => [], 'companies' => []]);
    }
}
