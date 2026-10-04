<?php

namespace App\Http\Controllers;

use App\Support\RecentlyDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configs › Recently deleted: restore deleted records, or remove them for
 * good. Most are removed automatically 30 days after deletion; employees
 * and companies are kept until restored.
 */
class RecentlyDeletedController extends Controller
{
    public function index(Request $request): View
    {
        $type = array_key_exists((string) $request->query('type'), RecentlyDeleted::TYPES) ? $request->query('type') : null;

        return view('configs.recently-deleted', [
            'rows' => RecentlyDeleted::all($type),
            'type' => $type,
            'types' => collect(RecentlyDeleted::TYPES)->map(fn ($t) => $t[1]),
            'retention' => RecentlyDeleted::RETENTION_DAYS,
        ]);
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        $model = $this->find($type, $id);
        $name = RecentlyDeleted::describe($model)['title'];

        try {
            $model->restore();
        } catch (UniqueConstraintViolationException) {
            return back()->with('error', __(':name can\'t be restored: another :type now has the same name. Rename or delete that one first.', ['name' => $name, 'type' => mb_strtolower(__(RecentlyDeleted::TYPES[$type][1]))]));
        }

        return back()->with('status', __('Restored: :name.', ['name' => $name]));
    }

    /**
     * Undo of a bulk delete: restore the given records of a type.
     */
    public function restoreMany(Request $request, string $type): RedirectResponse
    {
        $class = RecentlyDeleted::TYPES[$type][0] ?? abort(404);
        $ids = array_filter((array) $request->input('ids'), 'is_numeric');
        $restored = 0;

        foreach ($class::onlyTrashed()->whereKey($ids)->get() as $model) {
            try {
                $model->restore();
                $restored++;
            } catch (UniqueConstraintViolationException) {
                // a live record took its name; it stays in Recently deleted
            }
        }

        return back()->with('status', trans_choice('Restored :count record.|Restored :count records.', $restored));
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        abort_unless(RecentlyDeleted::expires($type), 403, 'Employees and companies can only be restored.');
        $model = $this->find($type, $id);
        $name = RecentlyDeleted::describe($model)['title'];
        $model->prune(); // removes stored files too, then the record

        return back()->with('status', __('Deleted for good: :name.', ['name' => $name]));
    }

    private function find(string $type, int $id): Model
    {
        $class = RecentlyDeleted::TYPES[$type][0] ?? abort(404);

        return $class::onlyTrashed()->findOrFail($id);
    }
}
