<?php

namespace Tests\Feature;

use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FirmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['email' => 'asha@example.com']));
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'establishment_name' => 'Srympex Firm', 'sector' => 'Proprietory Firms', 'organization_type' => 'Proprietorship',
            'establishment_category' => 'Factory', 'registered_on' => '2015-04-01', 'registration_no' => 'UDYAM-GJ-01-0012345',
            'is_factory_registered' => '1', 'pan' => 'abcde1234f', 'address_line_1' => 'Plot 12, GIDC', 'district' => 'Bharuch', 'state' => 'Gujarat',
            'pin_code' => '392001', 'nearest_police_station' => 'Ankleshwar GIDC',
            'owners' => [
                ['first_name' => 'Jatin', 'last_name' => 'Patel', 'designation' => 'Proprietor', 'pan' => 'PQRSX9876Z', 'mobile' => '9876543210', 'is_primary' => '1', 'is_factory_manager' => '1'],
                ['first_name' => '', 'last_name' => '', 'is_primary' => '0', 'is_factory_manager' => '0'], // blank row: dropped
            ],
            'factory_manager' => ['first_name' => 'Jatin', 'last_name' => 'Patel', 'gender' => 'male', 'email' => 'jatin@example.com'],
            'mail_protocol' => 'SMTP', 'mail_host' => 'smtp.gmail.com', 'mail_port' => 465, 'mail_username' => 'hr@example.com', 'mail_password' => 'app-pass-123',
            'mail_type' => 'html', 'step' => 2,
        ], $overrides);
    }

    public function test_firm_page_shows_the_three_steps(): void
    {
        $this->get(route('company.firm'))->assertOk()
            ->assertSeeInOrder(['Company', 'Devices', 'Firm', 'ID Card Formats'])
            ->assertSeeInOrder(['Contractor firm', '1. Establishment Details', '2. Owners Details', '3. Mail setup'])
            ->assertSeeInOrder(['Establishment Name', 'Sector (Ownership Type)', 'Organization Type', 'Establishment Category', 'Nearest Police Station', 'Stamp image'])
            ->assertSee('Add owner')->assertSee('Factory Manager Details:')->assertSee('SMTP host');
    }

    public function test_firm_details_owners_and_mail_setup_are_saved_with_secrets_encrypted(): void
    {
        $this->put(route('company.firm.update'), $this->details(['stamp' => UploadedFile::fake()->image('stamp.png')]))
            ->assertSessionHasNoErrors()->assertRedirect(route('company.firm', ['step' => 2]));

        $firm = Firm::sole();
        $this->assertSame(['Srympex Firm', 'ABCDE1234F', true, '392001'], [$firm->establishment_name, $firm->pan, $firm->is_factory_registered, $firm->pin_code]);
        $this->assertCount(1, $firm->owners);
        $this->assertSame(['Jatin', 'Patel', 'Proprietor', 'PQRSX9876Z', '1', '1'], [$firm->owners[0]['first_name'], $firm->owners[0]['last_name'], $firm->owners[0]['designation'], $firm->owners[0]['pan'], $firm->owners[0]['is_primary'], $firm->owners[0]['is_factory_manager']]);
        $this->assertSame('jatin@example.com', $firm->factory_manager['email']);
        $this->assertSame('app-pass-123', $firm->mail_password);
        Storage::disk('local')->assertExists($firm->stamp_path);
        $this->get(route('company.firm.image', 'stamp'))->assertOk();
        $this->get(route('company.firm.image', 'signature'))->assertNotFound();

        // PAN, owners, factory manager and the SMTP password are stored encrypted.
        $raw = DB::table('firms')->first();
        foreach (['pan' => 'ABCDE1234F', 'owners' => 'PQRSX9876Z', 'factory_manager' => 'jatin@example.com', 'mail_password' => 'app-pass-123'] as $column => $secret) {
            $this->assertStringNotContainsString($secret, $raw->{$column}, $column);
        }

        // A blank password keeps the saved one; the page shows saved values.
        $this->put(route('company.firm.update'), $this->details(['mail_password' => '', 'establishment_name' => 'Srympex']))->assertSessionHasNoErrors();
        $this->assertSame('app-pass-123', $firm->fresh()->mail_password);
        $this->get(route('company.firm'))->assertSee('value="Srympex"', false)->assertSee('Saved — leave blank to keep')->assertSee('Jatin')->assertSee('Send test email to asha@example.com');
        $this->assertSame(['transport' => 'smtp', 'scheme' => 'smtps', 'host' => 'smtp.gmail.com', 'port' => 465, 'username' => 'hr@example.com', 'password' => 'app-pass-123'], $firm->fresh()->mailConfig());
    }

    public function test_firm_details_are_validated(): void
    {
        $this->put(route('company.firm.update'), $this->details(['establishment_name' => '', 'pan' => 'ABC', 'pin_code' => '12', 'sector' => 'Martian', 'owners' => [['first_name' => 'A', 'email' => 'not-an-email']]]))
            ->assertSessionHasErrors(['establishment_name', 'pan', 'pin_code', 'sector', 'owners.0.email']);
        $this->assertSame(0, Firm::count());
    }

    public function test_test_email_needs_a_saved_mail_setup(): void
    {
        $this->post(route('company.firm.test-mail'))->assertSessionHas('error', 'Save the mail setup (host, user and password) first.');
    }
}
