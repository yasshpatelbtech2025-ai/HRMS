<x-app-layout>
    <x-slot name="header">{{ __('Company') }}</x-slot>

    <x-section-tabs section="company" />

    @php
        $sort = request('sort') === 'size' ? 'size' : 'name';
        $direction = request('direction') === 'desc' ? 'desc' : 'asc';
        $sortUrl = fn (string $column) => request()->fullUrlWithQuery([
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
            'page' => null,
        ]);
        $arrow = fn (string $column) => $sort === $column ? ($direction === 'asc' ? '▲' : '▼') : '';
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Company List') }}</h2>
            <button type="button" x-data x-on:click="$dispatch('open-modal', 'create-company')"
                    class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 print:hidden">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('Add company') }}
            </button>
        </div>

        {{-- Table controls --}}
        <form method="GET" action="{{ route('company.index') }}"
              class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm print:hidden">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="direction" value="{{ request('direction') }}">

            <label class="flex items-center gap-2 text-gray-600">
                {{ __('Show') }}
                <select name="per_page" onchange="this.form.submit()" class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (\App\Http\Controllers\CompanyController::PER_PAGE as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
                {{ __('entries') }}
            </label>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('company.export', request()->only('search', 'sort', 'direction')) }}"
                   class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">{{ __('CSV') }}</a>
                <button type="button" onclick="window.print()"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">{{ __('Print') }}</button>
                <label class="flex items-center gap-2 text-gray-600">
                    {{ __('Search:') }}
                    <x-text-input type="search" name="search" :value="request('search')" class="py-1.5 text-sm" />
                </label>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <th class="px-5 py-3">
                            <a href="{{ $sortUrl('name') }}" class="inline-flex items-center gap-1 hover:text-gray-900">{{ __('Establishment Name') }} <span class="text-xs">{{ $arrow('name') }}</span></a>
                        </th>
                        <th class="px-5 py-3">
                            <a href="{{ $sortUrl('size') }}" class="inline-flex items-center gap-1 hover:text-gray-900">{{ __('Organization size') }} <span class="text-xs">{{ $arrow('size') }}</span></a>
                        </th>
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
                                <div class="flex items-center gap-1">
                                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-company-{{ $company->uuid }}')"
                                            title="{{ __('Edit') }}" class="rounded-md p-2 text-indigo-600 hover:bg-indigo-50">
                                        <span class="sr-only">{{ __('Edit') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>
                                    <form method="POST" action="{{ route('company.destroy', $company) }}"
                                          onsubmit="return confirm(@js(__('Delete :name? This cannot be undone from here.', ['name' => $company->establishment_name])))">
                                        @csrf
                                        @method('DELETE')
                                        <button title="{{ __('Delete') }}" class="rounded-md p-2 text-red-600 hover:bg-red-50">
                                            <span class="sr-only">{{ __('Delete') }}</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </form>
                                    <a href="{{ route('compliance.index', ['company' => $company->uuid]) }}" title="{{ __('Compliance') }}"
                                       class="rounded-md p-2 text-gray-600 hover:bg-gray-100">
                                        <span class="sr-only">{{ __('Compliance') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                    </a>
                                </div>

                                <x-modal name="edit-company-{{ $company->uuid }}" :show="old('_modal') === 'edit-company-'.$company->uuid" max-width="2xl">
                                    @include('companies._form', ['modal' => 'edit-company-'.$company->uuid])
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">
                                {{ request('search') ? __('No companies match your search.') : __('No companies yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-5 py-4 text-sm text-gray-600 print:hidden">
            <p>
                @if ($companies->total())
                    {{ __('Showing :from to :to of :total entries', ['from' => $companies->firstItem(), 'to' => $companies->lastItem(), 'total' => $companies->total()]) }}
                @else
                    {{ __('Showing 0 entries') }}
                @endif
            </p>
            {{ $companies->onEachSide(1)->links() }}
        </div>
    </div>

    <x-modal name="create-company" :show="old('_modal') === 'create-company'" max-width="2xl">
        @include('companies._form', ['company' => new \App\Models\Company, 'modal' => 'create-company'])
    </x-modal>

    {{-- Shared suggestions for the State / District / Taluka fields; any new value can be typed. --}}
    <datalist id="company-states">@foreach ($states as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="company-districts">@foreach ($districts as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="company-talukas">@foreach ($talukas as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
</x-app-layout>
