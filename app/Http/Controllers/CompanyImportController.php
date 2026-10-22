<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\Activity;
use App\Support\SheetImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Import companies": adds every row of a filled-in sheet as a company.
 * Nothing is saved unless every row is valid.
 */
class CompanyImportController extends Controller
{
    private const HEADINGS = [
        'Establishment Name *', 'Connected company', 'Address Line 1', 'Address Line 2', 'City', 'Village', 'Taluka', 'District', 'State', 'Country',
    ];

    private const FIELDS = [
        'establishment_name', 'parent', 'address_line_1', 'address_line_2', 'city', 'village', 'taluka', 'district', 'state', 'country',
    ];

    public function template(): StreamedResponse
    {
        return SheetImport::template('companies-template.xlsx', self::HEADINGS, [['Example Industries Pvt Ltd', '', '12 Main Road', '', 'Surat', 'Example Village', 'Choryasi', 'Surat', 'Gujarat', 'India']]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], ['file.mimes' => __('The file must be an .xlsx or .csv file. Save .xls files as .xlsx first.')]);

        $file = $request->file('file');
        $sheet = SheetImport::read($file->getRealPath(), strtolower($file->getClientOriginalExtension()) === 'csv' ? 'csv' : 'xlsx', self::HEADINGS);

        if ($sheet === []) {
            return back()->withErrors(['file' => __('The file has no company rows under the headings.')]);
        }

        $existing = Company::pluck('id', 'establishment_name')->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);
        $inFile = collect($sheet)->map(fn ($row) => mb_strtolower($row[self::HEADINGS[0]]))->filter()->all();
        $errors = [];
        $rows = [];

        foreach ($sheet as $number => $row) {
            $data = array_combine(self::FIELDS, array_values($row));
            $parent = mb_strtolower($data['parent']);

            if ($data['establishment_name'] === '') {
                $errors[] = __('Row :row: the establishment name is required.', ['row' => $number]);
            } elseif (mb_strlen($data['establishment_name']) > 255) {
                $errors[] = __('Row :row: the establishment name is too long.', ['row' => $number]);
            }

            if ($parent !== '' && ! $existing->has($parent) && ! in_array($parent, $inFile, true)) {
                $errors[] = __('Row :row: the connected company ":name" does not exist.', ['row' => $number, 'name' => $data['parent']]);
            }

            $rows[$number] = $data;
        }

        if ($errors) {
            return back()->with('import_errors', $errors);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $data) {
                $parent = Company::whereRaw('lower(establishment_name) = ?', [mb_strtolower($data['parent'])])->value('id');
                Company::create(array_merge(collect($data)->except('parent')->map(fn ($v) => $v === '' ? null : $v)->all(), ['parent_company_id' => $data['parent'] === '' ? null : $parent]));
            }
        });
        Activity::record('bulk', description: __('Imported :count company(ies) from :file', ['count' => count($rows), 'file' => $file->getClientOriginalName()]));

        return back()->with('status', trans_choice('{1} 1 company imported.|[2,*] :count companies imported.', count($rows), ['count' => count($rows)]));
    }
}
