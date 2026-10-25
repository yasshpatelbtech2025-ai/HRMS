<x-app-layout>
    <x-slot name="header">{{ __('Company') }}</x-slot>

    <x-section-tabs section="company" />

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('User access') }}</h1>
            </div>
            <x-add-button modal="create-user">{{ __('Add login') }}</x-add-button>
        </div>
        <x-stat-strip :items="collect($seats)->map(fn ($seat, $role) => [
            'label' => __(':role seats in use', ['role' => __(\App\Models\User::ROLES[$role])]),
            'value' => $seat['used'].' / '.$seat['cap'],
            'tone' => $seat['used'] >= $seat['cap'] ? 'warn' : null,
            'hint' => $seat['used'] >= $seat['cap'] ? __('No seats left') : __(':n left', ['n' => $seat['cap'] - $seat['used']]),
        ])->values()->all()" />

        <div class="card">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Name') }}</th>
                        <th class="px-5 py-3">{{ __('Role') }}</th>
                        <th class="px-5 py-3">{{ __('Companies') }}</th>
                        <th class="px-5 py-3">{{ __('Status') }}</th>
                        <th class="px-5 py-3">{{ __('Last sign-in') }}</th>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($users as $user)
                        @php $modal = 'edit-user-'.$user->id; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $user->name }} @if ($user->is(auth()->user()))<span class="text-xs font-normal text-gray-500">({{ __('you') }})</span>@endif</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->isAdmin() ? 'bg-ink text-canvas' : 'bg-brand-50 text-brand-700' }}">{{ __(\App\Models\User::ROLES[$user->role] ?? $user->role) }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-700">
                                @if ($user->isAdmin())
                                    <span class="text-gray-500">{{ __('All companies') }}</span>
                                @else
                                    {{ $user->companies->pluck('establishment_name')->join(', ') ?: '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-3"><x-status-badge :active="$user->is_active" /></td>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-700" @if ($user->last_login_at) title="{{ $user->last_login_at->format('d M Y H:i') }}" @endif>
                                {{ $user->last_login_at?->diffForHumans() ?? __('Never') }}
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" x-data x-on:click="$dispatch('open-modal', @js($modal))"
                                        class="rounded-md px-3 py-1.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">{{ __('Edit') }}</button>
                                <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="2xl">
                                    @include('companies._user-form')
                                </x-modal>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <x-modal name="create-user" :show="old('_modal') === 'create-user'" max-width="2xl">
        @include('companies._user-form', ['user' => new \App\Models\User, 'modal' => 'create-user'])
    </x-modal>
</x-app-layout>
