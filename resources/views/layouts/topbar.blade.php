<header class="sticky top-0 z-30 flex h-[var(--topbar-h)] items-center gap-2 border-b border-line bg-surface/95 px-3 backdrop-blur supports-[backdrop-filter]:bg-surface/85 sm:gap-3 sm:px-6 lg:px-8 print:hidden">
    <button type="button" @click="drawer = true" aria-controls="sidebar"
            class="-ms-1 inline-flex size-9 items-center justify-center rounded-control text-ink-2 hover:bg-sunken hover:text-ink lg:hidden">
        <span class="sr-only">{{ __('Open menu') }}</span>
        <x-icon name="menu" />
    </button>

    @php $currentCompany = \App\Support\CurrentCompany::get(); @endphp
    {{-- Company picker: most work is per client company, so it is always visible.
         The list loads on first open (prefetched on hover/focus). --}}
    <div x-data="{
            open: false, q: '', companies: null, active: 0, pos: '',
            async load() { if (this.companies) return; const r = await fetch(@js(route('context.companies')), { headers: { Accept: 'application/json' } }); this.companies = r.ok ? await r.json() : []; },
            get list() { const q = this.q.trim().toLowerCase(); return [{ id: '', name: @js(__('All companies')), all: true, current: {{ $currentCompany ? 'false' : 'true' }} }, ...(this.companies ?? [])].filter(c => !q || (!c.all && c.name.toLowerCase().includes(q))); },
            toggle() { this.open = !this.open; this.q = ''; this.active = 0; if (this.open) { this.load(); this.$nextTick(() => { this.pos = placeBelow(this.$refs.trigger, this.$refs.panel); this.$refs.filter.focus(); }); } },
            pick(c) { if (c) { this.$refs.value.value = c.id; this.$refs.form.submit(); } },
         }"
         class="relative min-w-0" @keydown.escape.stop="open = false; $refs.trigger.focus()" @click.outside="open = false">
        <button type="button" x-ref="trigger" @click="toggle()" @pointerenter.once="load()" @focus.once="load()" :aria-expanded="open" aria-haspopup="listbox"
                class="flex h-9 max-w-[13rem] items-center gap-2 rounded-control border px-2 text-sm transition-colors duration-fast sm:max-w-xs {{ $currentCompany ? 'border-line-strong bg-surface hover:border-field' : 'border-line-strong bg-canvas text-ink-2 hover:border-field hover:bg-surface' }}">
            @if ($currentCompany)
                <span class="flex size-6 shrink-0 items-center justify-center rounded-[5px] text-[11px] font-semibold text-white" style="background: hsl({{ \App\Support\CurrentCompany::hue($currentCompany->id) }} 42% 38%)">{{ mb_strtoupper(mb_substr($currentCompany->establishment_name, 0, 2)) }}</span>
                <span class="sr-only">{{ __('Company') }}:</span>
                <span class="min-w-0 truncate text-start font-medium text-ink">{{ $currentCompany->establishment_name }}</span>
            @else
                <x-icon name="building" class="size-4 text-ink-3" />
                <span class="truncate">{{ __('All companies') }}</span>
            @endif
            <x-icon name="chevron-up-down" class="size-4 shrink-0 text-ink-3" />
        </button>

        <div x-show="open" x-ref="panel" :style="pos" x-transition.opacity.duration.100ms style="display: none;" class="fixed z-[45] w-[min(20rem,calc(100vw-1.5rem))] rounded-card border border-line bg-surface shadow-pop">
            <div class="border-b border-line p-2">
                <input x-ref="filter" x-model="q" @input="active = 0" type="text" placeholder="{{ __('Find a company…') }}" aria-label="{{ __('Find a company') }}"
                       role="combobox" aria-controls="company-picker-list" :aria-activedescendant="'company-opt-' + active"
                       @keydown.down.prevent="active = Math.min(active + 1, list.length - 1)" @keydown.up.prevent="active = Math.max(active - 1, 0)" @keydown.enter.prevent="pick(list[active])"
                       class="control h-control-sm min-h-0 px-2.5 text-13">
            </div>
            <form x-ref="form" method="POST" action="{{ route('context.company') }}">
                @csrf
                <input type="hidden" name="company" x-ref="value">
            </form>
            <ul id="company-picker-list" role="listbox" aria-label="{{ __('Companies') }}" class="max-h-80 overflow-y-auto p-1">
                <template x-for="(c, i) in list" :key="c.id">
                    <li :id="'company-opt-' + i" role="option" :aria-selected="c.current" @click="pick(c)" @mousemove="active = i"
                        :class="[i === active ? 'bg-sunken' : '', c.current ? 'text-primary-ink font-medium' : 'text-ink']"
                        class="flex cursor-pointer items-center gap-2.5 rounded-[5px] px-2.5 py-2 text-sm">
                        <template x-if="c.all"><span class="flex size-6 items-center justify-center rounded-[5px] border border-dashed border-line-strong"><x-icon name="building" class="size-3.5 text-ink-3" /></span></template>
                        <template x-if="!c.all"><span class="flex size-6 shrink-0 items-center justify-center rounded-[5px] text-[10px] font-semibold text-white" :style="`background: hsl(${c.hue} 42% 38%)`" x-text="c.initials"></span></template>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate" x-text="c.name"></span>
                            <span class="block truncate text-xs font-normal text-ink-3" x-show="c.district" x-text="c.district"></span>
                        </span>
                        <x-icon name="check" class="size-4 text-primary" x-show="c.current" />
                    </li>
                </template>
                <li x-show="companies === null" class="space-y-2 p-2.5" aria-hidden="true"><div class="skeleton h-3 w-2/3"></div><div class="skeleton h-3 w-1/2"></div></li>
                <li x-show="companies && companies.length && list.length === 0" class="px-2.5 py-3 text-13 text-ink-3">{{ __('No match.') }}</li>
            </ul>
        </div>
    </div>

    <div class="min-w-0 flex-1">
        <x-global-search />
    </div>

    {{-- Light / dark theme: remembered in this browser; follows the system until chosen. --}}
    <button type="button" x-data="{ dark: document.documentElement.classList.contains('dark') }"
            @click="dark = ! dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('theme', dark ? 'dark' : 'light') } catch (e) {}"
            :aria-label="dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))" :title="dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
            class="inline-flex size-9 items-center justify-center rounded-control text-ink-2 hover:bg-sunken hover:text-ink">
        <x-icon name="sun" class="size-5" x-show="dark" style="display: none;" />
        <x-icon name="moon" class="size-5" x-show="! dark" />
    </button>

    <x-ui.menu width="w-60">
        <x-slot:trigger>
            <button type="button" class="flex items-center gap-2 rounded-control p-1 hover:bg-sunken" aria-label="{{ __('Account menu') }}" aria-haspopup="menu">
                <span class="flex size-7 items-center justify-center rounded-full bg-primary-soft text-xs font-semibold text-primary-ink">{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                <span class="hidden max-w-[10rem] truncate text-sm font-medium text-ink-2 md:block">{{ Auth::user()->name }}</span>
                <x-icon name="chevron-down" class="hidden size-3.5 text-ink-3 md:block" />
            </button>
        </x-slot:trigger>
        <div class="border-b border-line px-2.5 pb-2 pt-1.5">
            <p class="truncate text-sm font-medium text-ink">{{ Auth::user()->name }}</p>
            <p class="truncate text-xs text-ink-3">{{ Auth::user()->email }}</p>
        </div>
        <div class="pt-1">
            <x-ui.menu-item :href="route('profile.edit')" icon="user">{{ __('Profile') }}</x-ui.menu-item>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.menu-item type="submit" icon="logout">{{ __('Log Out') }}</x-ui.menu-item>
            </form>
        </div>
    </x-ui.menu>
</header>
