<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Global search (top bar): employees by name, employee ID or phone,
 * companies by name, and the app's pages by title.
 */
class SearchController extends Controller
{
    private const LIMIT = 6;

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q'));
        if (mb_strlen($query) < 2) {
            return response()->json(['employees' => [], 'companies' => [], 'pages' => []]);
        }
        $terms = collect(preg_split('/\s+/', mb_strtolower(mb_substr($query, 0, 100))))->filter()->take(5);
        $like = fn (string $term) => '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        // Every word must match one of the employee's fields ("gopal bhat", "0012", "98765").
        $employees = Employee::query()
            ->tap(function (Builder $q) use ($terms, $like) {
                foreach ($terms as $term) {
                    $q->where(fn (Builder $w) => $w->whereRaw('lower(first_name) like ?', [$like($term)])->orWhereRaw('lower(middle_name) like ?', [$like($term)])
                        ->orWhereRaw('lower(last_name) like ?', [$like($term)])->orWhereRaw('lower(employee_code) like ?', [$like($term)])
                        ->orWhereRaw('lower(phone) like ?', [$like($term)]));
                }
            })
            ->with(['company', 'position'])->orderBy('first_name')->limit(self::LIMIT)->get()
            ->map(fn (Employee $e) => [
                'title' => $e->full_name,
                'subtitle' => collect([$e->employee_code, $e->position?->name, $e->company?->establishment_name, $e->isSeparated() ? __('Separated') : null])->filter()->implode(' · '),
                'initials' => mb_strtoupper(mb_substr($e->first_name, 0, 1).mb_substr($e->last_name, 0, 1)),
                'url' => route('employees.show', $e),
            ]);

        $companies = Company::query()->whereRaw('lower(establishment_name) like ?', [$like($terms->implode(' '))])
            ->with('parent')->withCount('employees')->orderBy('establishment_name')->limit(self::LIMIT)->get()
            ->map(fn (Company $c) => [
                'title' => $c->establishment_name,
                'subtitle' => collect([$c->parent?->establishment_name ? __('Under :parent', ['parent' => $c->parent->establishment_name]) : null, trans_choice(':count employee|:count employees', $c->employees_count)])->filter()->implode(' · '),
                'url' => route('company.index', ['search' => $c->establishment_name]),
            ]);

        return response()->json(['employees' => $employees, 'companies' => $companies, 'pages' => $this->pages($terms->all())]);
    }

    /**
     * The sidebar sections and their tabs whose title matches every word.
     *
     * @param  list<string>  $terms
     * @return list<array{title: string, subtitle: string, url: string}>
     */
    private function pages(array $terms): array
    {
        $sections = ['dashboard' => 'Dashboard'] + collect(config('navigation'))->keys()->mapWithKeys(fn ($s) => [$s => Str::headline($s)])->all();
        $pages = [];
        foreach (config('navigation') as $section => $tabs) {
            foreach ($tabs as $key => $label) {
                $name = $section.'.'.($key === '' ? 'index' : $key);
                if (! Route::has($name)) {
                    continue;
                }
                $haystack = mb_strtolower($label.' '.$sections[$section]);
                if (collect($terms)->every(fn ($t) => str_contains($haystack, $t))) {
                    $pages[] = ['title' => __($label), 'subtitle' => __($sections[$section]), 'url' => route($name)];
                }
            }
        }

        return array_slice($pages, 0, self::LIMIT);
    }
}
