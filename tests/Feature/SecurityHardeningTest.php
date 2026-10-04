<?php

namespace Tests\Feature;

use App\Actions\SaveEmployee;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use App\Models\User;
use App\Support\TableExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_carry_security_headers(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
    }

    public function test_spreadsheet_exports_do_not_run_formulas(): void
    {
        $this->assertSame(["'=HYPERLINK(\"x\")", "'+1+2", "'@SUM(A1)", '-500.00', 'Asha', 12], TableExport::cells(['=HYPERLINK("x")', '+1+2', '@SUM(A1)', '-500.00', 'Asha', 12]));
        $this->assertInstanceOf(\OpenSpout\Common\Entity\Cell\StringCell::class, TableExport::row(['=1+1'])->cells[0]);

        $this->actingAs(User::factory()->create());
        Company::factory()->create(['establishment_name' => '=HYPERLINK("http://evil.test","Click")']);

        $csv = $this->get(route('company.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_a_captured_photo_must_really_be_an_image(): void
    {
        $jpeg = 'data:image/jpeg;base64,'.base64_encode(UploadedFile::fake()->image('p.jpg', 40, 40)->getContent());
        $html = 'data:image/png;base64,'.base64_encode('<html><script>alert(1)</script></html>');

        $this->assertSame('jpg', SaveEmployee::photoExtension($jpeg));
        $this->assertNull(SaveEmployee::photoExtension($html));
        $this->assertNull(SaveEmployee::photoExtension('data:image/png;base64,!!!'));
    }

    public function test_sign_in_attempts_from_one_address_are_limited(): void
    {
        foreach (range(1, 20) as $i) {
            $this->post('/login', ['email' => "nobody{$i}@example.com", 'password' => 'wrong'])->assertStatus(302);
        }

        $this->post('/login', ['email' => 'another@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_exports_are_limited_per_account(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (range(1, 30) as $i) {
            $this->get(route('company.export'))->assertOk();
        }

        $this->get(route('company.export'))->assertStatus(429);
        // Ordinary pages still open.
        $this->get(route('company.index'))->assertOk();
    }

    public function test_a_deleted_records_attachment_is_kept_until_it_is_removed_for_good(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $employee = Employee::factory()->create();
        Storage::disk('local')->put('salary-advances/x/note.pdf', 'pdf');
        $advance = SalaryAdvance::create(['company_id' => $employee->company_id, 'employee_id' => $employee->id, 'amount' => 1000, 'salary_month' => '2026-09-01', 'advance_date' => '2026-09-03']);
        $advance->forceFill(['attachment_path' => 'salary-advances/x/note.pdf'])->save();

        $this->delete(route('payroll.advances.destroy', $advance))->assertRedirect();
        $this->assertSoftDeleted($advance);
        Storage::disk('local')->assertExists('salary-advances/x/note.pdf');

        $this->travel(31)->days();
        $this->artisan('model:prune', ['--model' => [SalaryAdvance::class]]);
        $this->assertModelMissing($advance);
        Storage::disk('local')->assertMissing('salary-advances/x/note.pdf');
    }
}
