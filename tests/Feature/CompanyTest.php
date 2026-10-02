<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_cannot_see_companies(): void
    {
        $this->get(route('company.index'))->assertRedirect('/login');
        $this->post(route('company.store'), ['establishment_name' => 'X'])->assertRedirect('/login');
    }

    public function test_index_lists_and_searches_companies(): void
    {
        $this->login();
        Company::factory()->create(['establishment_name' => 'Alpha Pharma']);
        Company::factory()->create(['establishment_name' => 'Beta Packaging']);

        $this->get(route('company.index'))->assertOk()->assertSee('Alpha Pharma')->assertSee('Beta Packaging');
        $this->get(route('company.index', ['search' => 'Alpha']))->assertSee('Alpha Pharma')->assertDontSee('Beta Packaging');
    }

    public function test_company_can_be_created_with_a_connected_parent(): void
    {
        $this->login();
        $parent = Company::factory()->create();

        $this->post(route('company.store'), [
            'establishment_name' => 'Child Works',
            'parent_company_id' => $parent->id,
            'organization_size' => 12,
            'district' => 'Bharuch',
        ])->assertRedirect(route('company.index'));

        $this->assertDatabaseHas('companies', ['establishment_name' => 'Child Works', 'parent_company_id' => $parent->id, 'organization_size' => 12]);
    }

    public function test_name_is_required_and_parent_must_exist(): void
    {
        $this->login();

        $this->post(route('company.store'), ['establishment_name' => '', 'parent_company_id' => 999])
            ->assertSessionHasErrors(['establishment_name', 'parent_company_id']);
    }

    public function test_company_can_be_updated_but_not_its_own_parent(): void
    {
        $this->login();
        $company = Company::factory()->create();

        $this->put(route('company.update', $company), ['establishment_name' => 'Renamed'])->assertRedirect(route('company.index'));
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'establishment_name' => 'Renamed']);

        $this->put(route('company.update', $company), ['establishment_name' => 'Renamed', 'parent_company_id' => $company->id])
            ->assertSessionHasErrors('parent_company_id');
    }

    public function test_edit_page_renders(): void
    {
        $this->login();
        $company = Company::factory()->create();

        $this->get(route('company.edit', $company))->assertOk()->assertSee($company->establishment_name);
    }

    public function test_company_can_be_deleted_unless_it_has_connected_companies(): void
    {
        $this->login();
        $parent = Company::factory()->create();
        $child = Company::factory()->create(['parent_company_id' => $parent->id]);

        $this->delete(route('company.destroy', $parent))->assertSessionHas('error');
        $this->assertNotSoftDeleted($parent);

        $this->delete(route('company.destroy', $child))->assertRedirect(route('company.index'));
        $this->assertSoftDeleted($child);
    }
}
