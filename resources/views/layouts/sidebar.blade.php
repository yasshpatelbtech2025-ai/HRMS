{{--
    Sidebar: grouped sections with collapsible sub-menus. Sub-menu items come from
    config/navigation.php (the same list as each section's tabs), so adding a tab there
    adds it here too; admin-only pages are hidden from users (config/access.php).
    Desktop can collapse to an icon rail (remembered); below lg it is an off-canvas drawer.
--}}
@php
    use App\Support\CompanyAccess;

    $groups = [
        null => [['dashboard', 'Dashboard', 'home']],
        // Order is fixed (tests/Feature/SidebarTest): Company, Attendance, Employee, ...
        'Organisation' => [['company', 'Company', 'building']],
        'People' => [['attendance', 'Attendance', 'calendar'], ['employees', 'Employees', 'users']],
        'Compliance' => [['compliance', 'Compliance', 'shield'], ['documents', 'Documents', 'document']],
        'Money' => [['payroll', 'Payroll', 'wallet']],
        'Insights' => [['reports', 'Reports', 'chart']],
        'Admin' => [['configs', 'Configs', 'cog']],
    ];

    // Same active-tab rule as components/section-tabs: a tab owns its own routes and
    // the ones beneath it; anything else in the section belongs to the landing tab.
    $children = function (string $section) {
        $tabs = collect(config("navigation.{$section}", []))
            ->mapWithKeys(fn ($label, $key) => [($key === '' ? "{$section}.index" : "{$section}.{$key}") => $label])
            ->filter(fn ($label, $name) => CompanyAccess::canOpen($name) && Route::has($name));
        $landing = "{$section}.index";
        $active = request()->routeIs("{$section}.*")
            ? ($tabs->keys()->reject(fn ($n) => $n === $landing)->first(fn ($n) => request()->routeIs($n, "{$n}.*")) ?? $landing)
            : null;

        return $tabs->map(fn ($label, $name) => ['route' => $name, 'label' => $label, 'active' => $name === $active]);
    };
@endphp

<div x-show="drawer" x-transition.opacity @click="drawer = false" class="fixed inset-0 z-40 bg-black/40 lg:hidden" style="display: none;"></div>

<aside id="sidebar" aria-label="{{ __('Main') }}" :data-open="drawer"
       class="app-sidebar fixed inset-y-0 start-0 z-50 flex flex-col border-e border-line bg-rail transition-[transform,width] duration-200 print:hidden max-lg:shadow-pop">
    <div class="flex h-[var(--topbar-h)] shrink-0 items-center justify-between gap-2 px-3.5">
        <a href="{{ route('dashboard') }}" class="min-w-0 rounded-md" aria-label="{{ __('SRYMPEX home') }}">
            <x-brand-logo class="rail-hide" />
            <span class="rail-show hidden h-8 w-8 items-center justify-center rounded-md bg-[#d4141f] text-lg font-bold text-white" aria-hidden="true">S</span>
        </a>
        <button type="button" class="rounded-md p-1.5 text-ink-3 hover:bg-sunken hover:text-ink lg:hidden" @click="drawer = false" aria-label="{{ __('Close menu') }}"><x-icon name="x" class="size-4" /></button>
    </div>

    <nav class="flex-1 overflow-y-auto overscroll-contain px-2.5 pb-4" x-data x-init="$nextTick(() => $el.querySelector('[aria-current=page]')?.scrollIntoView({ block: 'nearest' }))">
        @foreach ($groups as $groupLabel => $items)
            <div class="mt-4 first:mt-1">
                @if ($groupLabel)
                    <p class="rail-hide mb-1 px-2.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-3">{{ __($groupLabel) }}</p>
                    <div class="rail-show mx-2.5 mb-2 hidden border-t border-line"></div>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($items as [$section, $label, $icon])
                        @php
                            $route = $section === 'dashboard' ? 'dashboard' : "{$section}.index";
                            $active = $section === 'dashboard' ? request()->routeIs('dashboard') : request()->routeIs("{$section}.*");
                            $subs = $section === 'dashboard' ? collect() : $children($section);
                            $base = 'flex w-full items-center gap-2.5 rounded-md px-2.5 py-[7px] text-sm transition-colors';
                            $state = $active ? 'bg-primary-soft font-medium text-primary-ink' : 'text-ink-2 hover:bg-sunken hover:text-ink';
                        @endphp
                        @if ($subs->count() > 1)
                            <li x-data="{ expanded: $persist({{ $active ? 'true' : 'false' }}).as('nav:{{ $section }}') }" @if ($active) x-init="expanded = true" @endif>
                                {{-- Icon rail: the section icon links straight to its landing page. --}}
                                <a href="{{ route($route) }}" title="{{ __($label) }}" class="rail-show hidden justify-center {{ $base }} {{ $state }}">
                                    <x-icon :name="$icon" class="size-[18px]" /><span class="sr-only">{{ __($label) }}</span>
                                </a>
                                <button type="button" @click="expanded = !expanded" :aria-expanded="expanded" aria-controls="nav-{{ $section }}"
                                        class="rail-hide {{ $base }} {{ $active ? 'font-medium text-ink' : 'text-ink-2 hover:bg-sunken hover:text-ink' }}">
                                    <x-icon :name="$icon" class="size-[18px] {{ $active ? 'text-primary' : '' }}" />
                                    <span class="flex-1 truncate text-start">{{ __($label) }}</span>
                                    <x-icon name="chevron-down" class="size-3.5 text-ink-3 transition-transform" ::class="expanded || '-rotate-90'" />
                                </button>
                                <ul id="nav-{{ $section }}" x-show="expanded" x-collapse class="rail-hide ms-[21px] mt-0.5 space-y-0.5 border-s border-line ps-2.5" @unless ($active) style="display: none;" @endunless>
                                    @foreach ($subs as $sub)
                                        <li>
                                            <a href="{{ route($sub['route']) }}" @if ($sub['active']) aria-current="page" @endif
                                               class="relative block rounded-md px-2.5 py-1.5 text-13 transition-colors {{ $sub['active'] ? 'bg-primary-soft font-medium text-primary-ink before:absolute before:-start-[11px] before:inset-y-1.5 before:w-0.5 before:rounded-full before:bg-primary' : 'text-ink-2 hover:bg-sunken hover:text-ink' }}">
                                                {{ __($sub['label']) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li>
                                <a href="{{ route($route) }}" title="{{ __($label) }}" @if ($active) aria-current="page" @endif class="rail-center {{ $base }} {{ $state }}">
                                    <x-icon :name="$icon" class="size-[18px] {{ $active ? 'text-primary' : '' }}" />
                                    <span class="rail-hide truncate">{{ __($label) }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="hidden shrink-0 border-t border-line p-2.5 lg:block">
        <button type="button" @click="rail = !rail" class="rail-center flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-13 text-ink-3 hover:bg-sunken hover:text-ink"
                :aria-label="rail ? @js(__('Expand sidebar')) : @js(__('Collapse sidebar'))" :title="rail ? @js(__('Expand sidebar')) : @js(__('Collapse sidebar'))">
            <x-icon name="sidebar" class="size-[18px]" />
            <span class="rail-hide">{{ __('Collapse') }}</span>
        </button>
    </div>
</aside>
