<x-app-layout>
    <x-slot name="header">{{ __('Company') }}</x-slot>

    <x-section-tabs section="company" />

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Company List') }}</h1>
            @can('admin')
                <div class="flex flex-wrap items-center gap-2 print:hidden">
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'import-companies')"
                            class="inline-flex items-center gap-2 rounded-md border border-line-strong bg-surface text-ink shadow-hairline hover:bg-sunken px-3 py-2 text-sm font-medium">{{ __('Import Companies') }}</button>
                    <x-add-button modal="create-company">{{ __('Add company') }}</x-add-button>
                </div>
            @endcan
        </div>
        <x-import-errors />
        <x-stat-strip :items="[
            ['label' => __('Companies'), 'value' => $companies->total()],
            ['label' => __('Employees across companies'), 'value' => $employeeCount],
        ]" />

        <div class="card">

        <x-list-toolbar :action="route('company.index')" :per-page="$perPage" print
                        :exports="[__('CSV') => route('company.export', request()->only('search', 'sort', 'direction'))]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="name" default>{{ __('Establishment Name') }}</x-sort-header>
                        <x-sort-header column="size">{{ __('Organization size') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Connected company') }}</th>
                        <th class="px-5 py-3 print:hidden">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($companies as $company)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $companies->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $company->establishment_name }}</td>
                            <td class="px-5 py-3">{{ $company->employees_count }}</td>
                            <td class="px-5 py-3">{{ $company->parent?->establishment_name ?? '-' }}</td>
                            <td class="px-5 py-3 print:hidden">
                                <x-row-actions :editable="Gate::allows('admin')" :edit-modal="'edit-company-'.$company->uuid" :delete-url="route('company.destroy', $company)"
                                               :delete-confirm="__('Delete :name? This cannot be undone from here.', ['name' => $company->establishment_name])">
                                    <a href="{{ route('compliance.index', ['company' => $company->uuid]) }}" title="{{ __('Compliance') }}"
                                       class="rounded-md p-2 text-gray-600 hover:bg-gray-100">
                                        <span class="sr-only">{{ __('Compliance') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                    </a>
                                </x-row-actions>

                                @can('admin')
                                    <x-modal name="edit-company-{{ $company->uuid }}" :show="old('_modal') === 'edit-company-'.$company->uuid" max-width="2xl">
                                        @include('companies._form', ['modal' => 'edit-company-'.$company->uuid])
                                    </x-modal>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        @if (request('search'))
                            <x-empty-state colspan="5" icon="search" :title="__('No companies match your search.')">{{ __('Try a different name.') }}</x-empty-state>
                        @else
                            <x-empty-state colspan="5" icon="building" :title="__('No companies yet')">
                                {{ __('Add the companies you manage; employees, attendance and payroll are kept per company.') }}
                                <x-slot name="action">
                                    @can('admin')<x-add-button modal="create-company">{{ __('Add company') }}</x-add-button>@endcan
                                </x-slot>
                            </x-empty-state>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$companies" />
    </div>
    </div>

    @can('admin')
        <x-bulk-upload name="import-companies" :title="__('Import Companies (Excel)')" :action="route('company.import')" :template="route('company.import.template')"
                       :hint="__('Connected company must be the exact name of an existing company, or of another company in the same file.')" />
        <x-modal name="create-company" :show="old('_modal') === 'create-company'" max-width="2xl">
            @include('companies._form', ['company' => new \App\Models\Company, 'modal' => 'create-company'])
        </x-modal>
    @endcan

    {{-- Shared suggestions for the City / Taluka / District / State / Country fields; any new value can be typed. --}}
    <datalist id="company-cities">@foreach ($cities as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="company-talukas">@foreach ($talukas as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="company-districts">@foreach ($districts as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="company-states">@foreach ($states as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="company-countries">@foreach ($countries as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
</x-app-layout>
