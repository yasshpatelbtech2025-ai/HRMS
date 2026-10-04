<?php

namespace App\Http\Controllers;

use App\Rules\AccessibleCompany;
use App\Actions\SaveEmployee;
use App\Http\Requests\EmployeeRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Support\EmployeeImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Import Employees (Excel)": a company-specific template, and adding every
 * row of a filled-in sheet as an employee of that company. Nothing is saved
 * unless every row is valid.
 */
class EmployeeImportController extends Controller
{
    public function template(Company $company): StreamedResponse
    {
        return (new EmployeeImport($company))->template();
    }

    public function store(Request $request, SaveEmployee $save): RedirectResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], ['file.mimes' => __('The file must be an .xlsx or .csv file. Save .xls files as .xlsx first.')]);

        $file = $request->file('file');
        $rows = (new EmployeeImport(Company::findOrFail($request->integer('company_id'))))
            ->read($file->getRealPath(), strtolower($file->getClientOriginalExtension()) === 'csv' ? 'csv' : 'xlsx');

        if ($rows === []) {
            return back()->with('error', __('The file has no employee rows under the headings.'));
        }

        $errors = [];
        $emails = [];

        foreach ($rows as $number => $data) {
            $rules = (new EmployeeRequest)->merge($data);
            $validator = Validator::make($data, $rules->rules(), [], $rules->attributes())->after($rules->after());
            $messages = $validator->errors()->all();

            if (filled($data['email']) && in_array(mb_strtolower($data['email']), $emails, true)) {
                $messages[] = __('The email is used on an earlier row of this file.');
            }
            $emails[] = mb_strtolower((string) $data['email']);

            if ($messages) {
                $errors[] = __('Row :row: :errors', ['row' => $number, 'errors' => implode(' ', $messages)]);
            }
        }

        if ($errors) {
            return back()->with('import_errors', $errors)
                ->with('error', __('Nothing was imported. Fix the rows below and upload the file again.'));
        }

        DB::transaction(function () use ($rows, $save) {
            foreach ($rows as $data) {
                $save(new Employee, $data);
            }
        });

        return back()->with('status', trans_choice(':count employee imported.|:count employees imported.', count($rows)));
    }
}
