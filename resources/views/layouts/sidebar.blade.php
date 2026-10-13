{{-- Sidebar: grouped sections from config/navigation.php. Desktop can collapse to an icon rail (remembered);
     below lg it is an off-canvas drawer. --}}
<div x-show="drawer" x-transition.opacity @click="drawer = false" class="fixed inset-0 z-40 bg-black/40 lg:hidden" x-cloak></div>

<aside id="sidebar" aria-label="{{ __('Main') }}"
       :data-open="drawer"
       class="app-sidebar fixed inset-y-0 start-0 z-50 flex flex-col border-e border-line bg-rail transition-[transform,width] duration-200 max-lg:shadow-pop">
    <div class="flex h-[var(--topbar-h)] shrink-0 items-center justify-between gap-2 px-3.5">
        <a href="{{ route('dashboard') }}" class="min-w-0 rounded-control">
            <x-ui.brand class="rail-hide" />
            <x-ui.brand compact class="rail-show hidden" />
        </a>
        <x-ui.button variant="ghost" size="sm" square type="button" class="lg:hidden" @click="drawer = false" aria-label="{{ __('Close menu') }}"><x-icon name="x" class="size-4" /></x-ui.button>
    </div>

    <nav class="flex-1 overflow-y-auto overscroll-contain px-2.5 pb-4">
        @foreach ($navigation->groups() as $group)
            <div class="mt-4 first:mt-1">
                @if ($group['label'])
                    <p class="rail-hide mb-1 px-2.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-3">{{ __($group['label']) }}</p>
                    <div class="rail-show mx-2.5 mb-2 hidden border-t border-line"></div>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php
                            $key = 'nav:'.$item['route'];
                            $base = 'flex w-full items-center gap-2.5 rounded-control px-2.5 py-[7px] text-sm transition-colors';
                            $state = $item['active'] ? 'bg-primary-soft font-medium text-primary-ink' : 'text-ink-2 hover:bg-sunken hover:text-ink';
                        @endphp
                        <li @if ($item['children']) x-data="{ expanded: $persist({{ $item['active'] ? 'true' : 'false' }}).as('{{ $key }}') }" @if ($item['active']) x-init="expanded = true" @endif @endif>
                            @if ($item['children'])
                                {{-- Parent: toggles sub-menu; in rail mode it links straight to the section. --}}
                                <a href="{{ route($item['route']) }}" title="{{ __($item['label']) }}" class="rail-show-flex hidden {{ $base }} {{ $state }} justify-center">
                                    <x-icon :name="$item['icon']" class="size-[18px]" />
                                    <span class="sr-only">{{ __($item['label']) }}</span>
                                </a>
                                <button type="button" @click="expanded = !expanded" :aria-expanded="expanded" aria-controls="sub-{{ $loop->parent->index }}-{{ $loop->index }}"
                                        class="rail-hide {{ $base }} {{ $item['active'] ? 'font-medium text-ink' : 'text-ink-2 hover:bg-sunken hover:text-ink' }}">
                                    <x-icon :name="$item['icon']" class="size-[18px] {{ $item['active'] ? 'text-primary' : '' }}" />
                                    <span class="flex-1 truncate text-start">{{ __($item['label']) }}</span>
                                    <x-icon name="chevron-down" class="size-3.5 text-ink-3 transition-transform" ::class="expanded || '-rotate-90'" />
                                </button>
                                <ul id="sub-{{ $loop->parent->index }}-{{ $loop->index }}" x-show="expanded" x-collapse class="rail-hide relative ms-[21px] mt-0.5 space-y-0.5 border-s border-line ps-2.5">
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ route($child['route']) }}" @if ($child['active']) aria-current="page" @endif
                                               class="relative block rounded-control px-2.5 py-1.5 text-13 transition-colors {{ $child['active'] ? 'bg-primary-soft font-medium text-primary-ink before:absolute before:-start-[11px] before:inset-y-1.5 before:w-0.5 before:rounded-full before:bg-primary' : 'text-ink-2 hover:bg-sunken hover:text-ink' }}">
                                                {{ __($child['label']) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a href="{{ route($item['route']) }}" title="{{ __($item['label']) }}" @if ($item['active']) aria-current="page" @endif class="{{ $base }} {{ $state }} rail-center">
                                    <x-icon :name="$item['icon']" class="size-[18px] {{ $item['active'] ? 'text-primary' : '' }}" />
                                    <span class="rail-hide truncate">{{ __($item['label']) }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="hidden shrink-0 border-t border-line p-2.5 lg:block">
        <button type="button" @click="rail = !rail" class="flex w-full items-center gap-2.5 rounded-control px-2.5 py-1.5 text-13 text-ink-3 hover:bg-sunken hover:text-ink rail-center"
                :aria-label="rail ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'" :title="rail ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'">
            <x-icon name="sidebar" class="size-[18px]" />
            <span class="rail-hide">{{ __('Collapse') }}</span>
        </button>
    </div>
</aside>
