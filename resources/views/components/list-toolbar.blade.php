{{--
    "Show N entries", export buttons and search box above a list table.
    Keeps the current sort and filters when the page size or search changes.
--}}
@props(['action', 'perPage', 'exports' => [], 'print' => false, 'allowAll' => false])

<form method="GET" action="{{ $action }}"
      class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm print:hidden">
    @foreach (request()->except('per_page', 'search', 'page') as $name => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="flex items-center gap-2 text-gray-600">
        {{ __('Show') }}
        <select name="per_page" onchange="this.form.submit()" class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (\App\Support\PageSize::OPTIONS as $size)
                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
            @endforeach
            @if ($allowAll)
                <option value="all" @selected($perPage === \App\Support\PageSize::ALL)>{{ __('All') }}</option>
            @endif
        </select>
        {{ __('entries') }}
    </label>

    <div class="flex flex-wrap items-center gap-2">
        @foreach ($exports as $label => $url)
            <a href="{{ $url }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">{{ $label }}</a>
        @endforeach
        @if ($print)
            <button type="button" onclick="window.print()"
                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">{{ __('Print') }}</button>
        @endif
        <label class="flex items-center gap-2 text-gray-600">
            {{ __('Search:') }}
            <x-text-input type="search" name="search" :value="request('search')" class="py-1.5 text-sm" />
        </label>
    </div>
</form>
