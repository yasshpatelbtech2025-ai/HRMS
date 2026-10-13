<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_selected_company_is_remembered_and_filters_lists(): void
    {
        $alpha = Company::factory()->create(['establishment_name' => 'Alpha Works']);
        $beta = Company::factory()->create(['establishment_name' => 'Beta Works']);
        Employee::factory()->for($alpha)->create(['first_name' => 'Asha']);
        Employee::factory()->for($beta)->create(['first_name' => 'Bhavin']);

        $this->from(route('employees.index', ['company_id' => $alpha->id, 'page' => 2]))
            ->post(route('context.company'), ['company_id' => $beta->id])
            ->assertRedirect(route('employees.index')); // old company filters dropped

        $this->assertSame($beta->id, session(CurrentCompany::SESSION_KEY));
        $this->get(route('employees.index'))->assertSee('Bhavin')->assertDontSee('Asha');

        // An explicit filter in the URL still wins, including "all companies".
        $this->get(route('employees.index', ['company_id' => '']))->assertSee('Asha')->assertSee('Bhavin');
    }

    public function test_clearing_the_selection_shows_all_companies(): void
    {
        $company = Company::factory()->create();
        session([CurrentCompany::SESSION_KEY => $company->id]);

        $this->post(route('context.company'), ['company_id' => ''])->assertRedirect();

        $this->assertNull(session(CurrentCompany::SESSION_KEY));
    }

    public function test_unknown_company_is_rejected(): void
    {
        $this->post(route('context.company'), ['company_id' => 999])->assertSessionHasErrors('company_id');
    }

    public function test_switcher_options_mark_the_current_company(): void
    {
        $company = Company::factory()->create(['establishment_name' => 'Gamma Ltd']);
        session([CurrentCompany::SESSION_KEY => $company->id]);

        $this->getJson(route('context.companies'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Gamma Ltd', 'current' => true, 'initials' => 'GA']);
    }

    public function test_new_employee_form_defaults_to_the_selected_company(): void
    {
        $company = Company::factory()->create();
        session([CurrentCompany::SESSION_KEY => $company->id]);

        $this->get(route('employees.create'))->assertOk()->assertSee("combobox({ value: '{$company->id}'", false);
    }
}
