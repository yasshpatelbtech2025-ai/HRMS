{{--
    The tab bar for a sidebar section (config/navigation.php), or with
    `nested`, the tabs inside one of its tabs (config/subnavigation.php).
--}}
@props(['section', 'nested' => false])

@php
    $tabs = collect($nested ? config('subnavigation')[$section] ?? [] : config("navigation.{$section}", []))
        ->mapWithKeys(fn ($label, $key) => [($key === '' ? ($nested ? $section : "{$section}.index") : "{$section}.{$key}") => $label])
        // Admin-only tabs are not shown to users (config/access.php).
        ->filter(fn ($label, $name) => \App\Support\CompanyAccess::canOpen($name));

    // A tab is active on its own route and the routes beneath it (e.g.
    // employees.departments.*); anything else in the section belongs to the
    // landing tab (e.g. employees.create, employees.edit).
    $landing = $nested ? $section : "{$section}.index";
    $activeTab = $tabs->keys()->reject(fn ($name) => $name === $landing)->first(fn ($name) => request()->routeIs($name, "{$name}.*"))
        ?? $landing;
@endphp

@if (! $nested)
    {{-- Section pages: the tabs live in the sidebar sub-menu, so the top shows where you are. --}}
    @php
        $sectionLabel = [
            'company' => 'Company', 'attendance' => 'Attendance', 'employees' => 'Employees', 'compliance' => 'Compliance',
            'documents' => 'Documents', 'payroll' => 'Payroll', 'reports' => 'Reports', 'configs' => 'Configs',
        ][$section] ?? \Illuminate\Support\Str::headline($section);
        $current = $tabs[$activeTab] ?? null;
    @endphp
    <nav class="mb-1.5 print:hidden" aria-label="{{ __('Breadcrumb') }}">
        <ol class="flex flex-wrap items-center gap-1 text-13 text-ink-3">
            <li class="flex items-center gap-1">
                <a href="{{ route("{$section}.index") }}" class="rounded hover:text-ink hover:underline">{{ __($sectionLabel) }}</a>
            </li>
            @if ($current && __($current) !== __($sectionLabel))
                <li class="flex items-center gap-1">
                    <x-icon name="chevron-right" class="size-3" />
                    <a href="{{ route($activeTab) }}" aria-current="page" class="rounded text-ink-2 hover:text-ink hover:underline">{{ __($current) }}</a>
                </li>
            @endif
        </ol>
    </nav>
@elseif ($tabs->count() > 1)
    <nav class="mb-4 overflow-x-auto print:hidden" aria-label="{{ __('Sub-section') }}">
        <ul class="flex w-max gap-0.5 rounded-md bg-sunken p-0.5">
            @foreach ($tabs as $name => $label)
                @php $active = $name === $activeTab; @endphp
                <li>
                    <a href="{{ route($name) }}" @if ($active) aria-current="page" @endif
                       class="block whitespace-nowrap rounded-[5px] px-2.5 py-1 text-sm transition {{ $active ? 'bg-surface font-medium text-ink shadow-hairline' : 'text-ink-2 hover:text-ink' }}">
                        {{ __($label) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
