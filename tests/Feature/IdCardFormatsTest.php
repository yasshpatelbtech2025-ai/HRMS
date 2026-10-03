<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Firm;
use App\Models\User;
use App\Support\IdCardFormats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdCardFormatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_all_designs_are_complete(): void
    {
        $formats = IdCardFormats::all();
        $this->assertCount(51, $formats);
        $this->assertSame(10, $formats->where('orientation', 'landscape')->count());

        foreach ($formats as $format) {
            foreach (['front', 'back'] as $side) {
                $this->assertFileExists(public_path("images/id-cards/{$format['id']}-{$side}.jpg"));
            }
            $all = $format['front'].$format['back'];
            foreach (['name', 'photo', 'firm', 'id_no'] as $token) {
                $this->assertStringContainsString('{{'.$token.'}}', $all, "design {$format['id']} has no {$token}");
            }
            $this->assertStringNotContainsString('ilaservice', $all);
        }
    }

    public function test_gallery_shows_the_selected_design_first_and_selects_another(): void
    {
        $this->get(route('company.id-card-formats'))->assertOk()
            ->assertSeeInOrder(['ID Card Format List', 'images/id-cards/2-front.jpg', 'Employee Name', 'Selected', 'images/id-cards/1-front.jpg', 'Select'], false)
            ->assertSee('Company › Firm');

        $this->post(route('company.id-card-formats.select'), ['format' => 17])->assertRedirect(route('company.id-card-formats'));
        $this->assertSame(17, Firm::sole()->id_card_format);
        $this->get(route('company.id-card-formats'))->assertSeeInOrder(['images/id-cards/17-front.jpg', 'Selected', 'images/id-cards/1-front.jpg'], false);

        $this->post(route('company.id-card-formats.select'), ['format' => 999])->assertSessionHasErrors('format');
    }

    public function test_employee_id_card_prints_in_the_selected_design_with_their_details(): void
    {
        Firm::create(['establishment_name' => 'Srympex Firm', 'district' => 'Bharuch', 'state' => 'Gujarat', 'id_card_format' => 3]);
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id, 'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0007', 'phone' => '9876501234',
            'blood_group' => 'B+', 'date_of_birth' => '1994-09-01', 'employment_type' => 'full_time', 'id_card_assigned_on' => '2026-09-20',
        ]);
        $employee->familyMembers()->create(['relation' => 'father', 'full_name' => 'Rooplal Bhat']);

        $this->get(route('employees.id-cards.show', $employee))->assertOk()
            ->assertSee('images/id-cards/3-front.jpg', false)->assertSee('images/id-cards/3-back.jpg', false)
            ->assertSeeInOrder(['Gopal Bhat', 'Rooplal Bhat', '0007', '01/09/1994', '9876501234', 'B+', 'Full Time'])
            ->assertSeeInOrder(['Srympex Firm', 'Bharuch, Gujarat', '20/09/2026', 'Signature of the Holder', 'Issuing Authority'])
            ->assertSee('images/id-cards/avatar.svg', false)
            ->assertDontSee('{{', false)->assertSee('Change the design');
    }
}
