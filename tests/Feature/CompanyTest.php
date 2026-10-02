<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
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

    public function test_index_shows_tabs_columns_and_organization_size_from_employees(): void
    {
        $this->login();
        $parent = Company::factory()->create(['establishment_name' => 'Srympex']);
        $company = Company::factory()->create(['establishment_name' => 'PI Industry', 'parent_company_id' => $parent->id]);
        Employee::factory()->count(4)->create(['company_id' => $company->id]);

        $this->get(route('company.index'))
            ->assertOk()
            ->assertSeeInOrder(['Company', 'Devices', 'Firm', 'ID Card Formats', 'Company List'])
            ->assertSeeInOrder(['Sl', 'Establishment Name', 'Organization size', 'Connected company', 'Action'])
            ->assertSeeInOrder(['PI Industry', '4', 'Srympex']);
    }

    public function test_index_searches_case_insensitively_and_paginates(): void
    {
        $this->login();
        Company::factory()->create(['establishment_name' => 'Alpha Pharma']);
        Company::factory()->create(['establishment_name' => 'Beta Packaging']);

        $this->get(route('company.index', ['search' => 'alpha']))->assertSee('Alpha Pharma')->assertSee('Showing 1 to 1 of 1 entries');

        Company::factory()->count(10)->create();
        $this->get(route('company.index'))->assertSee('Showing 1 to 10 of 12 entries');
        $this->get(route('company.index', ['per_page' => 25]))->assertSee('Showing 1 to 12 of 12 entries');
    }

    public function test_company_can_be_created_with_a_connected_parent(): void
    {
        $this->login();
        $parent = Company::factory()->create();

        $this->from(route('company.index'))->post(route('company.store'), [
            'establishment_name' => 'Child Works',
            'parent_company_id' => $parent->id,
            'state' => 'Gujarat',
            'district' => 'Bharuch',
            'taluka' => 'Ankleshwar',
        ])->assertRedirect(route('company.index'))->assertSessionHas('status');

        $this->assertDatabaseHas('companies', [
            'establishment_name' => 'Child Works', 'parent_company_id' => $parent->id,
            'state' => 'Gujarat', 'district' => 'Bharuch', 'taluka' => 'Ankleshwar',
        ]);
    }

    public function test_new_location_values_are_offered_afterwards(): void
    {
        $this->login();
        Company::factory()->create(['district' => 'Kachchh', 'taluka' => 'Gandhidham']);

        $this->get(route('company.index'))->assertSee('<option value="Kachchh">', false)->assertSee('<option value="Gandhidham">', false);
    }

    public function test_name_is_required_and_parent_must_exist(): void
    {
        $this->login();

        $this->post(route('company.store'), ['establishment_name' => '', 'parent_company_id' => 999])
            ->assertSessionHasErrors(['establishment_name', 'parent_company_id']);
    }

    public function test_company_cannot_be_connected_to_itself_or_its_own_connected_company(): void
    {
        $this->login();
        $company = Company::factory()->create();
        $child = Company::factory()->create(['parent_company_id' => $company->id]);

        $this->put(route('company.update', $company), ['establishment_name' => 'Renamed'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'establishment_name' => 'Renamed']);

        $this->put(route('company.update', $company), ['establishment_name' => 'Renamed', 'parent_company_id' => $company->id])
            ->assertSessionHasErrors('parent_company_id');
        $this->put(route('company.update', $company), ['establishment_name' => 'Renamed', 'parent_company_id' => $child->id])
            ->assertSessionHasErrors('parent_company_id');
    }

    public function test_failed_validation_reopens_the_modal_that_was_submitted(): void
    {
        $this->login();
        $company = Company::factory()->create();
        $modal = 'edit-company-'.$company->uuid;

        $this->from(route('company.index'))->followingRedirects()
            ->put(route('company.update', $company), ['_modal' => $modal, 'establishment_name' => ''])
            ->assertSee('The establishment name field is required.');
    }

    public function test_company_cannot_be_deleted_while_it_has_connected_companies_or_employees(): void
    {
        $this->login();
        $parent = Company::factory()->create();
        $child = Company::factory()->create(['parent_company_id' => $parent->id]);
        $staffed = Company::factory()->create();
        Employee::factory()->create(['company_id' => $staffed->id]);

        $this->delete(route('company.destroy', $parent))->assertSessionHas('error');
        $this->delete(route('company.destroy', $staffed))->assertSessionHas('error');
        $this->assertNotSoftDeleted($parent);
        $this->assertNotSoftDeleted($staffed);

        $this->delete(route('company.destroy', $child))->assertSessionHas('status');
        $this->assertSoftDeleted($child);
    }

    public function test_export_downloads_filtered_csv(): void
    {
        $this->login();
        Company::factory()->create(['establishment_name' => 'Alpha Pharma']);
        Company::factory()->create(['establishment_name' => 'Beta Packaging']);

        $csv = $this->get(route('company.export', ['search' => 'alpha']))->assertOk()->streamedContent();

        $this->assertStringContainsString('Alpha Pharma', $csv);
        $this->assertStringNotContainsString('Beta Packaging', $csv);
    }
}
