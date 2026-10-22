<?php

namespace App\Support;

use App\Enums\Gender;
use App\Models\Company;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The "Import Employees (Excel)" template and the reading of a filled-in
 * sheet into Add employee form data for one company.
 */
class EmployeeImport
{
    /**
     * Template column heading => form field. Headings marked * are required.
     */
    public const COLUMNS = [
        'First Name *' => 'first_name',
        'Middle Name' => 'middle_name',
        'Last Name *' => 'last_name',
        'Gender *' => 'gender',
        'Email *' => 'email',
        'Phone *' => 'phone',
        'Alternate Phone' => 'alternate_phone',
        'Date of Birth * (YYYY-MM-DD)' => 'date_of_birth',
        'Joining Date * (YYYY-MM-DD)' => 'date_of_joining',
        'Blood Group' => 'blood_group',
        'Aadhar Card No' => 'aadhar_card_no',
        'PAN Card No' => 'pan_card_no',
        'UAN No' => 'uan_code',
        'ESIC No' => 'ip_number',
        'Bank Name *' => 'bank_name',
        'Account Number *' => 'account_number',
        'IFSC Code *' => 'ifsc_code',
        'Branch Address' => 'branch_address',
        'MICR Code' => 'micr_code',
        'Pay frequency * (Monthly/Daily/Hourly)' => 'pay_frequency',
        'Basic (monthly)' => 'basic_monthly',
        'Daily rate' => 'daily_rate',
        'Hourly rate' => 'hourly_rate',
        'Daily hour *' => 'daily_hour',
        'Monthly salary calculation type *' => 'monthly_salary_calculation',
        'Weekly leave day *' => 'weekly_leave_day',
    ];

    public function __construct(private Company $company) {}

    /**
     * An .xlsx with the column headings on the first sheet and, on a second
     * "Lists" sheet, the values this company accepts for each choice column.
     */
    public function template(): StreamedResponse
    {
        $lists = $this->lists();

        return response()->streamDownload(function () use ($lists) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName('Employees');
            $writer->addRow(Row::fromValues(array_keys(self::COLUMNS)));

            $writer->addNewSheetAndMakeItCurrent()->setName('Lists');
            $writer->addRow(TableExport::row(array_keys($lists)));
            $longest = max(array_map('count', $lists));

            for ($i = 0; $i < $longest; $i++) {
                $writer->addRow(TableExport::row(array_map(fn ($values) => $values[$i] ?? '', array_values($lists))));
            }

            $writer->close();
        }, 'employees-'.Str::slug($this->company->establishment_name).'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Read the sheet into one array of form data per non-empty row, keyed
     * by spreadsheet row number. Unknown names (department, designation…)
     * become null so validation reports them.
     *
     * @return array<int, array<string, mixed>>
     */
    public function read(string $path, string $extension): array
    {
        $reader = $extension === 'csv' ? new CsvReader : new XlsxReader;
        $reader->open($path);
        $rows = [];
        $headings = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $number => $row) {
                $values = array_map(fn ($cell) => $cell instanceof DateTimeInterface ? $cell->format('Y-m-d') : trim((string) $cell), $row->toArray());

                if ($headings === null) {
                    $headings = array_map(fn ($heading) => $this->fieldFor($heading), $values);

                    continue;
                }

                if (collect($values)->filter(fn ($value) => $value !== '')->isEmpty()) {
                    continue;
                }

                $rows[$number] = $this->toFormData(array_filter(
                    array_combine(array_slice($headings, 0, count($values)), array_slice($values, 0, count($headings))),
                    fn ($value, $field) => $field !== '',
                    ARRAY_FILTER_USE_BOTH,
                ));
            }

            break; // Only the first sheet holds employees.
        }

        $reader->close();

        return $rows;
    }

    /**
     * @param  array<string, string>  $row  field => cell text
     * @return array<string, mixed>
     */
    private function toFormData(array $row): array
    {
        $lookup = fn (array $options, ?string $value) => $value === null || $value === '' ? null
            : (collect($options)->search(fn ($label, $key) => mb_strtolower($label) === mb_strtolower($value) || mb_strtolower((string) $key) === mb_strtolower($value)) ?: $value);

        return [
            'company_id' => $this->company->id,
            'first_name' => $row['first_name'] ?? null,
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'gender' => $lookup(collect(Gender::cases())->mapWithKeys(fn ($g) => [$g->value => $g->label()])->all(), $row['gender'] ?? null),
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'alternate_phone' => $row['alternate_phone'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'country' => 'India',
            'date_of_joining' => $row['date_of_joining'] ?? null,
            'blood_group' => isset($row['blood_group']) && $row['blood_group'] !== '' ? strtoupper($row['blood_group']) : null,
            'aadhar_card_no' => isset($row['aadhar_card_no']) ? preg_replace('/\s+/', '', $row['aadhar_card_no']) : null,
            'pan_card_no' => isset($row['pan_card_no']) ? strtoupper($row['pan_card_no']) : null,
            'bank_name' => $row['bank_name'] ?? null,
            'branch_address' => $row['branch_address'] ?? null,
            'account_number' => isset($row['account_number']) ? preg_replace('/\s+/', '', $row['account_number']) : null,
            'ifsc_code' => isset($row['ifsc_code']) ? strtoupper($row['ifsc_code']) : null,
            'micr_code' => $row['micr_code'] ?? null,
            'uan_code' => $row['uan_code'] ?? null,
            'ip_number' => $row['ip_number'] ?? null,
            'pay_frequency' => $lookup(config('employee.pay_frequencies'), $row['pay_frequency'] ?? null),
            'salary' => ['basic' => ['monthly' => $row['basic_monthly'] ?? null]],
            'daily_rate' => $row['daily_rate'] ?? null,
            'hourly_rate' => $row['hourly_rate'] ?? null,
            'daily_hour' => $row['daily_hour'] ?? null,
            'tax_regime' => 'new',
            'monthly_salary_calculation' => $lookup(array_map(fn ($label) => Str::before($label, ' —'), config('employee.monthly_salary_calculations')), $row['monthly_salary_calculation'] ?? null),
            'weekly_leave_day' => $lookup(config('employee.weekdays'), $row['weekly_leave_day'] ?? null),
            'permanent_address_is_present' => false,
            'is_disabled' => false,
        ];
    }

    private function fieldFor(string $heading): string
    {
        $normalize = fn (string $text) => Str::of($text)->before('(')->replace('*', '')->squish()->lower()->toString();

        foreach (self::COLUMNS as $label => $field) {
            if ($normalize($label) === $normalize($heading)) {
                return $field;
            }
        }

        return '';
    }

    /**
     * @return array<string, list<string>> heading => accepted values
     */
    private function lists(): array
    {
        return [
            'Gender' => array_map(fn ($g) => $g->label(), Gender::cases()),
            'Blood Group' => config('employee.blood_groups'),
            'Pay frequency' => array_values(config('employee.pay_frequencies')),
            'Monthly salary calculation type' => array_map(fn ($label) => Str::before($label, ' —'), array_values(config('employee.monthly_salary_calculations'))),
            'Weekly leave day' => array_values(config('employee.weekdays')),
        ];
    }
}
