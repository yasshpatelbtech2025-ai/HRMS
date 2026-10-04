<?php

namespace App\Http\Controllers;

use App\Rules\AccessibleCompany;
use App\Jobs\ImportEmployees;
use App\Models\Company;
use App\Models\EmployeeImportRun;
use App\Support\EmployeeImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Import Employees (Excel)": a company-specific template, and adding every
 * row of a filled-in sheet as an employee of that company, in the
 * background. Nothing is saved unless every row is valid.
 */
class EmployeeImportController extends Controller
{
    public function template(Company $company): StreamedResponse
    {
        return (new EmployeeImport($company))->template();
    }

    /**
     * Keep the upload and import it in the background (ImportEmployees); the
     * employee list shows its progress and outcome.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], ['file.mimes' => __('The file must be an .xlsx or .csv file. Save .xls files as .xlsx first.')]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension()) === 'csv' ? 'csv' : 'xlsx';
        $run = EmployeeImportRun::create([
            'company_id' => $request->integer('company_id'),
            'user_id' => $request->user()->id,
            'original_name' => mb_substr(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 0, 200).'.'.$extension,
            'path' => Storage::disk(config('filesystems.employee_files'))->putFileAs('imports', $file, Str::random(24).'.'.$extension),
        ]);

        ImportEmployees::dispatch($run);

        return back()->with('status', __('Import started. Its progress is shown at the top of the list.'));
    }

    /** Progress of an import, polled by the employee list. */
    public function status(Request $request, EmployeeImportRun $run): JsonResponse
    {
        abort_unless($run->user_id === $request->user()->id || $request->user()->isAdmin(), 404);

        return response()->json([
            'status' => $run->status,
            'finished' => $run->isFinished(),
            'imported' => $run->imported,
            'errors' => $run->errors ?? [],
        ]);
    }
}
