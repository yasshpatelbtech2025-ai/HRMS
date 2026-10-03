<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ComplianceFormValue;
use App\Models\ComplianceRegisterEntry;
use App\Models\Employee;
use App\Services\Compliance\ComplianceForms;
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
    private const LANDSCAPE = ['form-a', 'form-b', 'form-c', 'form-d', 'form-13', 'form-16', 'form-17', 'form-18', 'form-20', 'form-21', 'form-22', 'form-23'];

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
        $data = ['form' => $form, 'act' => $act, 'title' => $definition['title'], 'view' => $view] + $this->forms->build($view, $company, $request);

        return match ($request->query('output')) {
            'print' => response()->view('compliance.forms.document', $data + ['printing' => true, 'autoPrint' => true, 'landscape' => in_array($view, self::LANDSCAPE, true)]),
            'pdf' => $this->pdf($data, $view),
            default => view('compliance.forms.page', $data + ['printing' => false]),
        };
    }

    /**
     * Add rows to a register typed in by hand (Form C, 20, 21).
     */
    public function storeEntries(Request $request, Company $company, string $form): RedirectResponse
    {
        abort_unless(in_array($form, ['form-c', 'form-20', 'form-21'], true), 404);

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee' => ['required', 'uuid', Rule::exists('employees', 'uuid')->where('company_id', $company->id)],
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

        return back()->with('status', __('Row deleted.'));
    }

    /**
     * Save a return's fill-in fields and, optionally, its signed copy.
     */
    public function storeValues(Request $request, Company $company, string $form): RedirectResponse
    {
        abort_unless(in_array($form, ['form-25-half-yearly', 'bonus-form-d', 'gratuity-form-f'], true), 404);
        $view = config("compliance.acts.contract-labour-act.forms.{$form}.view");

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
        $html = view('compliance.forms.document', $data + ['printing' => true, 'autoPrint' => false, 'landscape' => in_array($view, self::LANDSCAPE, true)])->render();
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
