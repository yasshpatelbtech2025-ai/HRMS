<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPickerTest extends TestCase
{
    use RefreshDatabase;

    private Company $alpha;

    private Company $beta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alpha = Company::factory()->create(['establishment_name' => 'Alpha Works']);
        $this->beta = Company::factory()->create(['establishment_name' => 'Beta Works']);
    }

    public function test_picked_company_is_remembered_and_applied_to_pages(): void
    {
        $this->actingAs(User::factory()->create());
        Employee::factory()->create(['company_id' => $this->alpha->id, 'first_name' => 'Asha']);
        Employee::factory()->create(['company_id' => $this->beta->id, 'first_name' => 'Bhavin']);

        $this->from(route('employees.index', ['page' => 2]))
            ->post(route('context.company'), ['company' => $this->beta->uuid])
            ->assertRedirect(route('employees.index', ['company' => $this->beta->uuid]));

        $this->assertSame($this->beta->uuid, session(CurrentCompany::SESSION_KEY));
        $this->get(route('employees.index'))->assertSee('Bhavin')->assertDontSee('Asha');
        // An explicit filter in the address still wins.
        $this->get(route('employees.index', ['company_id' => '']))->assertSee('Asha')->assertSee('Bhavin');
    }

    public function test_choosing_a_company_in_a_page_filter_updates_the_picker(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('employees.index', ['company_id' => $this->alpha->id]))->assertOk();
        $this->assertSame($this->alpha->uuid, session(CurrentCompany::SESSION_KEY));

        $this->get(route('attendance.index', ['company' => $this->beta->uuid]))->assertOk();
        $this->assertSame($this->beta->uuid, session(CurrentCompany::SESSION_KEY));
    }

    public function test_all_companies_clears_the_pick(): void
    {
        $this->actingAs(User::factory()->create())->withSession([CurrentCompany::SESSION_KEY => $this->alpha->uuid]);

        $this->post(route('context.company'), ['company' => ''])->assertRedirect();

        $this->assertNull(session(CurrentCompany::SESSION_KEY));
    }

    public function test_users_only_see_and_pick_their_own_companies(): void
    {
        $this->actingAs(User::factory()->user([$this->alpha])->create());

        $this->getJson(route('context.companies'))->assertOk()
            ->assertJsonCount(1)->assertJsonFragment(['name' => 'Alpha Works']);

        $this->post(route('context.company'), ['company' => $this->beta->uuid])->assertSessionHas('error');
        $this->assertNull(session(CurrentCompany::SESSION_KEY));

        // A company they may not see in the address is never remembered.
        $this->get(route('attendance.index', ['company' => $this->beta->uuid]));
        $this->assertNull(session(CurrentCompany::SESSION_KEY));
    }

    public function test_picker_requires_login(): void
    {
        $this->getJson(route('context.companies'))->assertUnauthorized();
        $this->post(route('context.company'), ['company' => $this->alpha->uuid])->assertRedirect('/login');
    }
}
