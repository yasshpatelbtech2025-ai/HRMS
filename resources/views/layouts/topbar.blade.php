<header class="sticky top-0 z-30 flex h-[var(--topbar-h)] items-center gap-2 border-b border-line bg-canvas px-3 sm:gap-3 sm:px-6 lg:px-8 print:hidden">
    <button type="button" @click="drawer = true" aria-controls="sidebar"
            class="-ms-1 rounded-md p-2 text-ink-2 hover:bg-sunken hover:text-ink lg:hidden">
        <span class="sr-only">{{ __('Open menu') }}</span>
        <x-icon name="menu" />
    </button>

    @isset($header)
        <div class="hidden min-w-0 truncate text-sm font-medium text-ink-2 md:block md:max-w-[12rem]">{{ $header }}</div>
        <span class="hidden h-5 border-s border-line md:block" aria-hidden="true"></span>
    @endisset

    <div class="min-w-0 flex-1">
        <x-global-search />
    </div>

    <x-ui.menu width="w-60">
        <x-slot:trigger>
            <button type="button" class="flex items-center gap-2 rounded-md p-1 hover:bg-sunken" aria-label="{{ __('Account menu') }}" aria-haspopup="menu">
                <span class="flex size-7 items-center justify-center rounded-full bg-ink text-xs font-semibold text-canvas">{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
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
