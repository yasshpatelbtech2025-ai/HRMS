<?php

namespace App\Http\Controllers;

use App\Models\DocumentAssignment;
use App\Models\DocumentFile;
use App\Models\User;
use App\Support\DocumentSlots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents: the company's statutory and agreement documents by category,
 * each slot holding uploaded files and assigned to a member of staff.
 */
class DocumentController extends Controller
{
    public function index(): View
    {
        return view('documents.index', [
            'categories' => DocumentSlots::all(),
            'files' => DocumentFile::latest()->get()->groupBy('slot'),
            'assignments' => DocumentAssignment::pluck('user_id', 'slot'),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * All files in one slot.
     */
    public function show(string $slot): View
    {
        $found = DocumentSlots::find($slot) ?? abort(404);

        return view('documents.show', [
            'slot' => $found,
            'category' => config("documents.{$found['category']}.title"),
            'files' => DocumentFile::where('slot', $slot)->with('uploader')->latest()->get(),
        ]);
    }

    public function upload(Request $request, string $slot): RedirectResponse
    {
        $found = DocumentSlots::find($slot) ?? abort(404);

        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ], ['files.*.mimes' => __('Upload PDF, image, Word or Excel files.'), 'files.*.max' => __('Each file can be up to 10 MB.')]);

        $disk = Storage::disk(config('filesystems.employee_files'));

        foreach ($request->file('files') as $file) {
            DocumentFile::create([
                'slot' => $slot,
                'category' => $found['category'],
                'company_id' => $found['company_id'],
                'path' => $disk->putFileAs("documents/{$slot}", $file, Str::random(12).'.'.$file->guessExtension()),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return back()->with('status', trans_choice(':count file uploaded to :name.|:count files uploaded to :name.', count($request->file('files')), ['name' => $found['name']]));
    }

    public function file(DocumentFile $file): StreamedResponse
    {
        $disk = Storage::disk(config('filesystems.employee_files'));
        abort_unless($disk->exists($file->path), 404);

        return $disk->response($file->path, $file->original_name);
    }

    public function destroy(DocumentFile $file): RedirectResponse
    {
        Storage::disk(config('filesystems.employee_files'))->delete($file->path);
        $file->delete();

        return back()->with('status', __(':name deleted.', ['name' => $file->original_name]));
    }

    public function assign(Request $request, string $slot): RedirectResponse
    {
        $found = DocumentSlots::find($slot) ?? abort(404);
        $data = $request->validate(['user_id' => ['nullable', 'integer', Rule::exists('users', 'id')]]);

        if (empty($data['user_id'])) {
            DocumentAssignment::where('slot', $slot)->delete();
        } else {
            DocumentAssignment::updateOrCreate(['slot' => $slot], ['user_id' => $data['user_id']]);
        }

        return back()->with('status', __(':name assignment saved.', ['name' => $found['name']]));
    }
}
