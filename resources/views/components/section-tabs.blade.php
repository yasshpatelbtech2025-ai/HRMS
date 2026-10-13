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

@if ($tabs->count() > 1)
    <nav class="{{ $nested ? 'mb-4' : 'mb-5' }} overflow-x-auto print:hidden" aria-label="{{ $nested ? __('Sub-section') : __('Section') }}">
        <ul class="flex w-max {{ $nested ? 'gap-0.5 rounded-md bg-sunken p-0.5' : 'gap-4 border-b border-line' }}">
            @foreach ($tabs as $name => $label)
                @php $active = $name === $activeTab; @endphp
                <li>
                    <a href="{{ route($name) }}" @if ($active) aria-current="page" @endif
                       class="block whitespace-nowrap text-sm transition {{ $nested ? 'rounded-[5px] px-2.5 py-1' : '-mb-px border-b-2 px-0.5 pb-2.5 pt-1' }}
                              {{ $nested
                                  ? ($active ? 'bg-surface font-medium text-ink shadow-hairline' : 'text-ink-2 hover:text-ink')
                                  : ($active ? 'border-primary font-medium text-ink' : 'border-transparent text-ink-2 hover:border-line-strong hover:text-ink') }}">
                        {{ __($label) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
