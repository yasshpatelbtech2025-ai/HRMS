<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Company › User access (admins only): the logins, their role, and the
 * companies each user works on. Seats are capped per role
 * (config/access.php); accounts are deactivated rather than deleted, which
 * frees the seat and keeps their history.
 */
class UserAccessController extends Controller
{
    public function index(): View
    {
        $users = User::with(['companies' => fn ($q) => $q->orderBy('establishment_name')])
            ->orderByRaw("case when role = 'admin' then 0 else 1 end")->orderBy('name')->get();

        return view('companies.user-access', [
            'users' => $users,
            'companies' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
            'seats' => collect(config('access.seats'))->map(fn ($cap, $role) => [
                'used' => $users->where('role', $role)->where('is_active', true)->count(),
                'cap' => $cap,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureSeat($data['role'], $data['is_active']);

        DB::transaction(function () use ($data) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->forceFill(['role' => $data['role'], 'is_active' => $data['is_active'], 'email_verified_at' => now()])->save();
            $user->companies()->sync($data['role'] === 'user' ? $data['companies'] : []);
        });

        return back()->with('status', __('Login added for :name.', ['name' => $data['name']]));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        // An admin can't lock themselves out; this also keeps an active admin.
        if ($user->is($request->user()) && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            throw ValidationException::withMessages(['role' => __('You can\'t remove your own admin access or deactivate yourself. Ask the other admin.')]);
        }
        $this->ensureSeat($data['role'], $data['is_active'], $user);

        DB::transaction(function () use ($user, $data) {
            $user->fill(['name' => $data['name'], 'email' => $data['email']] + array_filter(['password' => $data['password']]));
            $user->forceFill(['role' => $data['role'], 'is_active' => $data['is_active']])->save();
            $user->companies()->sync($data['role'] === 'user' ? $data['companies'] : []);
        });
        CompanyAccess::forget();

        return back()->with('status', __('Access updated for :name.', ['name' => $user->name]));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['boolean'],
            'companies' => ['array', Rule::requiredIf($request->input('role') === 'user')],
            'companies.*' => ['integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
        ], ['companies.required' => __('Pick at least one company for this user.')]);

        return ['is_active' => $request->boolean('is_active'), 'companies' => $data['companies'] ?? [], 'password' => $data['password'] ?? null] + $data;
    }

    /**
     * Refuse an active account beyond the role's seat cap.
     */
    private function ensureSeat(string $role, bool $active, ?User $user = null): void
    {
        $cap = config("access.seats.{$role}");
        if (! $active || $cap === null) {
            return;
        }

        $used = User::where('role', $role)->where('is_active', true)->when($user, fn ($q) => $q->whereKeyNot($user->id))->count();
        if ($used >= $cap) {
            throw ValidationException::withMessages(['role' => trans_choice(
                'All :count :role seat is taken. Deactivate a login first.|All :count :role seats are taken. Deactivate a login first.',
                $cap, ['role' => strtolower(User::ROLES[$role])],
            )]);
        }
    }
}
