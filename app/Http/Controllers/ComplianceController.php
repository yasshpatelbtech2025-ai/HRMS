<?php

namespace App\Http\Controllers;

use App\Rules\AccessibleEmployee;
use App\Models\Company;
use App\Models\ComplianceFormValue;
use App\Models\ComplianceRegisterEntry;
use App\Models\Employee;
use App\Models\MedicalExamination;
use App\Services\Compliance\ComplianceForms;
use App\Support\TableExport;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Compliance › Forms: the acts and their statutory forms for a company, and
 * each form's page with print and PDF.
 */
class ComplianceController extends Controller
{
    /** Forms laid out in landscape when printed or saved as PDF. */
    private const LANDSCAPE = ['form-a', 'form-b', 'form-c', 'form-d', 'form-13', 'form-16', 'form-17', 'form-18', 'form-20', 'form-21', 'form-22', 'form-23', 'form-15', 'form-29', 'form-32', 'id-card-register', 'bonus-form-a', 'bonus-form-c'];

    public function __construct(private ComplianceForms $forms) {}

    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $acts = config('compliance.acts');

        return view('compliance.index', [
            'companies' => $companies,
            'company' => $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first(),
            'acts' => $acts,
            'act' => array_key_exists((string) $request->query('act'), $acts) ? $request->query('act') : array_key_first($acts),
        ]);
    }

    public function show(Request $request, Company $company, string $form): View|Response
    {
        [$act, $definition] = $this->definition($form);
        $view = $definition['view'] ?? abort(404);
        $noPdf = $definition['no_pdf'] ?? false;
        abort_if($noPdf && $request->query('output') === 'pdf', 404);
        $data = ['form' => $form, 'act' => $act, 'title' => $definition['title'], 'view' => $view, 'noPdf' => $noPdf] + $this->forms->build($view, $company, $request);

        return match ($request->query('output')) {
            'print' => response()->view('compliance.forms.document', $data + ['printing' => true, 'autoPrint' => true, 'landscape' => in_array($view, self::LANDSCAPE, true)]),
            'pdf' => $this->pdf($data, $view),
            'csv', 'xlsx' => isset($data['export'])
                ? TableExport::download($request->query('output'), Str::slug($definition['title']).'-'.$data['month']->format('Y-m'), $data['export']['headings'], $data['export']['rows'], $definition['title'].' — '.$company->establishment_name.' — '.$data['month']->format('F Y'))
                : abort(404),
            // A blank form (no employees yet) is shown read-only: there is nobody to save it for.
            default => view('compliance.forms.page', $data + ['printing' => (bool) ($data['blank'] ?? false)]),
        };
    }

    /**
     * Add rows to a register typed in by hand (Form C, 20, 21).
     */
    public function storeEntries(Request $request, Company $company, string $form): RedirectResponse
    {
        if ($form === 'form-29-accidents') {
            return $this->storeAccidents($request, $company);
        }
        abort_unless(in_array($form, ['form-c', 'form-20', 'form-21'], true), 404);

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->where('company_id', $company->id)],
            'rows.*.type' => [$form === 'form-c' ? 'required' : 'nullable', Rule::in(config('compliance.recovery_types'))],
            'rows.*.particular' => ['nullable', 'string', 'max:255'],
            'rows.*.date' => ['nullable', 'date'],
            'rows.*.amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'rows.*.show_cause' => ['nullable', Rule::in(['Yes', 'No'])],
            'rows.*.heard_by' => ['nullable', 'string', 'max:255'],
            'rows.*.installments' => ['nullable', 'integer', 'min:0', 'max:360'],
            'rows.*.first' => ['nullable', 'string', 'max:20'],
            'rows.*.last' => ['nullable', 'string', 'max:20'],
            'rows.*.recovered' => ['nullable', 'date'],
            'rows.*.wages' => ['nullable', 'string', 'max:255'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ], [], ['rows.*.employee' => 'employee', 'rows.*.type' => 'recovery type']);

        $ids = Employee::whereIn('uuid', collect($data['rows'])->pluck('employee'))->pluck('id', 'uuid');

        foreach ($data['rows'] as $row) {
            ComplianceRegisterEntry::create([
                'company_id' => $company->id, 'form' => $form, 'month' => $data['month'].'-01',
                'employee_id' => $ids[$row['employee']], 'data' => collect($row)->except('employee')->filter(fn ($v) => $v !== null && $v !== '')->all(),
            ]);
        }

        return back()->with('status', trans_choice(':count row saved.|:count rows saved.', count($data['rows'])));
    }

    public function destroyEntry(ComplianceRegisterEntry $entry): RedirectResponse
    {
        $entry->delete();

        return $this->deleted($entry, __('Row deleted.'));
    }

    /**
     * Save a return's fill-in fields and, optionally, its signed copy.
     */
    public function storeValues(Request $request, Company $company, string $form): RedirectResponse
    {
        $view = $this->definition($form)[1]['view'] ?? null;
        abort_unless(in_array($view, ['form-25', 'bonus-form-a', 'bonus-form-b', 'bonus-form-c', 'bonus-form-d', 'gratuity-form-f', 'er-1', 'pt-form-5', 'pf-statement', 'pf-form-10c', 'pf-form-19', 'maternity-k', 'maternity-l', 'maternity-m', 'maternity-n', 'police-verification'], true), 404);

        $data = $request->validate([
            'period' => ['required', 'string', 'max:20', 'regex:/^(\d{4}-\d{2}|\d{4}-\d{4}|employee:\d+)$/'],
            'raw_data' => ['nullable', 'array'],
            'raw_data.*' => ['nullable', 'string', 'max:1000'],
            'doc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [], ['doc' => 'signed copy']);

        $value = ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => $view, 'period' => $data['period']]);
        $value->data = array_filter($data['raw_data'] ?? [], fn ($v) => $v !== null && $v !== '');

        if ($request->hasFile('doc')) {
            $disk = Storage::disk(config('filesystems.employee_files'));
            if ($value->signed_copy_path) {
                $disk->delete($value->signed_copy_path);
            }
            $file = $request->file('doc');
            $value->signed_copy_path = $disk->putFileAs("compliance/{$company->uuid}/{$view}", $file, Str::random(12).'.'.$file->guessExtension());
            $value->signed_copy_name = $file->getClientOriginalName();
        }

        $value->save();

        // The police station is the same for every employee of the company.
        if ($view === 'police-verification') {
            $station = ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => $view, 'period' => 'company']);
            $station->data = array_filter(['station_name' => $value->value('station_name'), 'station_phone' => $value->value('station_phone')]);
            $station->save();
        }

        return back()->with('status', __('Saved.'));
    }

    public function signedCopy(Company $company, string $form, string $period): StreamedResponse
    {
        $value = ComplianceFormValue::where('company_id', $company->id)->where('form', $form)->where('period', $period)->firstOrFail();
        $disk = Storage::disk(config('filesystems.employee_files'));
        abort_unless($value->signed_copy_path && $disk->exists($value->signed_copy_path), 404);

        return $disk->response($value->signed_copy_path, $value->signed_copy_name);
    }

    /**
     * Form 29: add accidents. Each row is filed under the month it happened.
     */
    private function storeAccidents(Request $request, Company $company): RedirectResponse
    {
        $text = ['nullable', 'string', 'max:255'];
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->where('company_id', $company->id)],
            'rows.*.notice_at' => ['nullable', 'date'],
            'rows.*.esic' => ['nullable', 'string', 'max:30'],
            'rows.*.date' => ['required', 'date', 'before_or_equal:today'],
            'rows.*.time' => ['nullable', 'date_format:H:i'],
            'rows.*.place' => $text, 'rows.*.cause' => $text, 'rows.*.nature' => $text, 'rows.*.doing' => $text,
            'rows.*.notice_by' => $text, 'rows.*.witnesses' => $text,
            'rows.*.returned_on' => ['nullable', 'date', 'after_or_equal:rows.*.date'],
            'rows.*.days_absent' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ], [], ['rows.*.employee' => 'employee', 'rows.*.date' => 'date of accident', 'rows.*.returned_on' => 'date of return to work']);

        $ids = Employee::whereIn('uuid', collect($data['rows'])->pluck('employee'))->pluck('id', 'uuid');

        foreach ($data['rows'] as $row) {
            ComplianceRegisterEntry::create([
                'company_id' => $company->id, 'form' => 'form-29', 'month' => substr($row['date'], 0, 7).'-01',
                'employee_id' => $ids[$row['employee']], 'data' => collect($row)->except('employee')->filter(fn ($v) => $v !== null && $v !== '')->all(),
            ]);
        }

        return back()->with('status', trans_choice(':count accident saved.|:count accidents saved.', count($data['rows'])));
    }

    /**
     * Record a medical examination (Factory Act Form 5, 27-A, 30, 32).
     */
    public function storeExamination(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validateExamination($request, $company);
        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();
        $examination = new MedicalExamination(['company_id' => $company->id, 'employee_id' => $employee->id]);
        $this->fillExamination($examination, $data, $request, $company);
        $form = $request->input('form');

        return ($form && in_array($this->definition($form)[1]['view'] ?? null, config('compliance.examination_views'), true)
            ? redirect()->route('compliance.forms.show', [$company, $form, 'employee' => $employee->uuid, 'exam' => $examination->uuid])
            : back())->with('status', __('Examination saved for :name.', ['name' => $employee->full_name]));
    }

    public function updateExamination(Request $request, MedicalExamination $examination): RedirectResponse
    {
        $company = Company::findOrFail($examination->company_id);
        $data = $this->validateExamination($request, $company, $examination);
        $this->fillExamination($examination, $data, $request, $company);

        return back()->with('status', __('Examination updated.'));
    }

    public function destroyExamination(MedicalExamination $examination): RedirectResponse
    {
        $examination->delete(); // the signed copy stays until the record is pruned

        return $this->deleted($examination, __('Examination deleted.'));
    }

    public function examinationCopy(MedicalExamination $examination): StreamedResponse
    {
        $disk = Storage::disk(config('filesystems.employee_files'));
        abort_unless($examination->signed_copy_path && $disk->exists($examination->signed_copy_path), 404);

        return $disk->response($examination->signed_copy_path, $examination->signed_copy_name);
    }

    private function validateExamination(Request $request, Company $company, ?MedicalExamination $examination = null): array
    {
        $details = collect(MedicalExamination::DETAILS)->collapse()
            ->map(fn ($field) => $field[1] === 'date' ? ['nullable', 'date'] : ['nullable', 'string', 'max:500'])
            ->mapWithKeys(fn ($rules, $key) => ["details.{$key}" => $rules])->all();

        return $request->validate([
            'employee' => [$examination ? 'nullable' : 'required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->where('company_id', $company->id)],
            'examined_on' => ['required', 'date'],
            'result' => ['nullable', Rule::in(['fit', 'unfit'])],
            'certifying_surgeon' => ['nullable', 'string', 'max:255'],
            'signs_symptoms' => ['nullable', 'string', 'max:1000'],
            'extended_until' => ['nullable', 'date', 'after_or_equal:examined_on'],
            'details' => ['nullable', 'array'],
            'doc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ] + $details, [], ['examined_on' => 'examination date', 'doc' => 'signed copy', 'extended_until' => 'extended until']);
    }

    private function fillExamination(MedicalExamination $examination, array $data, Request $request, Company $company): void
    {
        $examination->fill([
            'examined_on' => $data['examined_on'],
            'result' => $data['result'] ?? null,
            'certifying_surgeon' => $data['certifying_surgeon'] ?? null,
            'signs_symptoms' => $data['signs_symptoms'] ?? null,
            'extended_until' => $data['extended_until'] ?? null,
            'details' => array_filter($data['details'] ?? [], fn ($v) => $v !== null && $v !== ''),
        ]);

        if ($request->hasFile('doc')) {
            $disk = Storage::disk(config('filesystems.employee_files'));
            if ($examination->signed_copy_path) {
                $disk->delete($examination->signed_copy_path);
            }
            $file = $request->file('doc');
            $examination->signed_copy_path = $disk->putFileAs("compliance/{$company->uuid}/examinations", $file, Str::random(12).'.'.$file->guessExtension());
            $examination->signed_copy_name = $file->getClientOriginalName();
        }

        $examination->save();
    }

    /**
     * @return array{string, array} act key and form definition
     */
    private function definition(string $form): array
    {
        foreach (config('compliance.acts') as $act => $definition) {
            if (isset($definition['forms'][$form])) {
                return [$act, $definition['forms'][$form]];
            }
        }

        abort(404);
    }

    private function pdf(array $data, string $view): Response
    {
        $html = view('compliance.forms.document', $data + ['printing' => true, 'pdf' => true, 'autoPrint' => false, 'landscape' => in_array($view, self::LANDSCAPE, true)])->render();
        $dompdf = new Dompdf((new Options)->set('isRemoteEnabled', false)->set('defaultFont', 'DejaVu Sans'));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', in_array($view, self::LANDSCAPE, true) ? 'landscape' : 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($data['title']).'-'.Str::slug($data['company']->establishment_name).'.pdf"',
        ]);
    }
}
