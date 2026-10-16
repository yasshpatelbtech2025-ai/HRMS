<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Payslip;
use App\Models\SalarySheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create(['establishment_name' => 'Thermax Onsite', 'district' => 'Bharuch', 'state' => 'Gujarat']);
        $this->actingAs(User::factory()->create());
    }

    /** The Songwon August 2026 invoice from the Excel. */
    private function songwon(array $overrides = []): array
    {
        return $overrides + [
            'company' => $this->company->uuid, 'invoice_no' => '26/27-199', 'invoice_date' => '2026-09-05', 'month' => '2026-08',
            'customer_name' => 'Thermax Onsite Energy Solutions Limited', 'customer_address' => "C/O Songwon\nBharuch, Gujarat -394116", 'customer_gst' => '24AADCT2702L1ZI',
            'lines' => [
                ['description' => 'Manpower Supply Charges Aug 26', 'amount' => 412505], ['description' => 'PH for Aug 26', 'amount' => 33103],
                ['description' => 'Employers PF Contribution @ 13%', 'amount' => 31315], ['description' => 'Employers ESIC @ 3.25%', 'amount' => 8270],
            ],
            'extra_lines' => [
                ['description' => 'Manpower Supply OT Charges Aug 26', 'amount' => 9676], ['description' => 'OT Arrear for July 26', 'amount' => 4410],
                ['description' => 'Food Expense', 'amount' => 5160], ['description' => 'Food Gst Amount', 'amount' => 516],
                ['description' => '', 'amount' => ''], // empty row ignored
            ],
            'service_charge_rate' => 8.5, 'gst_rate' => 18,
        ];
    }

    public function test_an_invoice_adds_up_like_the_excel_and_prints(): void
    {
        $this->post(route('invoice.store'), $this->songwon())->assertRedirect();

        $invoice = Invoice::sole();
        $totals = $invoice->totals();
        $this->assertSame(485193.0, $totals['sub_total']);
        $this->assertSame(41241.41, $totals['service_charge']);
        $this->assertSame(546196.41, $totals['total']);
        $this->assertSame(['IGST@18%' => 98315.35], $totals['taxes']);
        $this->assertSame('644511.76', $invoice->grand_total);
        $this->assertCount(4, $invoice->extra_lines);

        $this->get(route('invoice.show', $invoice))->assertOk()
            ->assertSeeInOrder(['TAX INVOICE', 'Thermax Onsite Energy Solutions Limited', '26/27-199', '05-09-2026', '27ABACS5355A1ZM',
                'Manpower Supply Charges Aug 26', 'Sub Total', '4,85,193.00', 'Service Charges@8.5%', 'Food Gst Amount', 'IGST@18%', 'Grand Total', '6,44,511.76'], false);
        $this->get(route('invoice.index'))->assertOk()->assertSee('26/27-199');

        $pdf = $this->get(route('invoice.pdf', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('invoice-2627-199-thermax-onsite-energy-solutions-limited.pdf', $pdf->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_same_state_customers_get_cgst_and_sgst(): void
    {
        $this->post(route('invoice.store'), $this->songwon(['customer_gst' => '27AAAAA0000A1Z5', 'lines' => [['description' => 'Supply', 'amount' => 1000]], 'extra_lines' => []]));

        $this->assertSame(['CGST@9%' => 97.65, 'SGST@9%' => 97.65], Invoice::sole()->totals()['taxes']);
    }

    public function test_invoice_numbers_are_unique_and_editable(): void
    {
        $this->post(route('invoice.store'), $this->songwon());
        $this->post(route('invoice.store'), $this->songwon())->assertSessionHasErrors('invoice_no');

        $invoice = Invoice::sole();
        $this->put(route('invoice.update', $invoice), $this->songwon(['service_charge_rate' => 0, 'extra_lines' => []]))->assertRedirect(route('invoice.show', $invoice));
        $this->assertSame('572527.74', $invoice->fresh()->grand_total); // 485193 × 1.18

        $this->delete(route('invoice.destroy', $invoice))->assertRedirect(route('invoice.index'));
        $this->assertSoftDeleted($invoice);
        $this->post(route('invoice.store'), $this->songwon())->assertSessionDoesntHaveErrors(); // number free again
    }

    public function test_fill_from_payroll(): void
    {
        $sheet = SalarySheet::create(['company_id' => $this->company->id, 'month' => '2026-08-01', 'generated_at' => now()]);
        $payslip = fn (array $attributes) => Payslip::create($attributes + [
            'salary_sheet_id' => $sheet->id, 'company_id' => $this->company->id, 'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
            'month' => '2026-08-01', 'pay_frequency' => 'monthly', 'total_deductions' => 0, 'net_pay' => 0,
        ]);
        // 31 days, 1 holiday paid: ₹31,000 earned → ₹1,000 is the holiday's share; PF ₹1,200 = 12% of ₹10,000.
        $payslip(['attendance' => ['paid_days' => 31, 'holidays' => 1], 'gross_earnings' => 31500,
            'earnings' => [['key' => 'basic', 'amount' => 31000], ['key' => 'overtime', 'amount' => 500]],
            'deductions' => [['key' => 'pf', 'amount' => 1200]], 'employer' => [['key' => 'employer_esi', 'amount' => 0]]]);
        $payslip(['attendance' => ['paid_days' => 20, 'holidays' => 0], 'gross_earnings' => 12000,
            'earnings' => [['key' => 'basic', 'amount' => 12000]],
            'deductions' => [], 'employer' => [['key' => 'employer_esi', 'amount' => 390]]]);

        $this->get(route('invoice.create', ['company' => $this->company->uuid, 'month' => '2026-08']))->assertOk()
            ->assertSee('Filled from the salary sheet for August 2026')
            ->assertViewHas('invoice', fn (Invoice $invoice) => $invoice->customer_name === 'Thermax Onsite'
                && $invoice->lines == [
                    ['description' => 'Manpower Supply Charges Aug 26', 'amount' => 42000.0],
                    ['description' => 'PH for Aug 26', 'amount' => 1000.0],
                    ['description' => 'Employers PF Contribution @ 13%', 'amount' => 1300.0], // 13% of ₹10,000
                    ['description' => 'Employers ESIC @ 3.25%', 'amount' => 390.0],
                ]
                && $invoice->extra_lines == [['description' => 'Manpower Supply OT Charges Aug 26', 'amount' => 500.0]]);

        $this->get(route('invoice.create', ['company' => $this->company->uuid, 'month' => '2026-07']))->assertOk()
            ->assertSee('No salary has been generated for this company for July 2026');
    }

    public function test_users_cannot_reach_invoices(): void
    {
        $this->post(route('invoice.store'), $this->songwon());
        $this->actingAs(User::factory()->user([$this->company])->create());

        $this->get(route('invoice.index'))->assertForbidden();
        $this->get(route('invoice.create'))->assertForbidden();
        $this->get(route('invoice.show', Invoice::sole()))->assertForbidden();
        $this->get(route('invoice.pdf', Invoice::sole()))->assertForbidden();
        $this->post(route('invoice.store'), $this->songwon(['invoice_no' => 'X']))->assertForbidden();
    }
}
