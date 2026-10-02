<header class="print:hidden sticky top-0 z-20 flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8 bg-white border-b border-gray-200">
    <div class="flex items-center gap-3">
        <!-- Mobile sidebar toggle -->
        <button type="button" @click="sidebarOpen = true"
                class="lg:hidden -ms-2 p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="sr-only">{{ __('Open sidebar') }}</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>

        @isset($header)
            <div class="text-lg font-semibold text-gray-800">{{ $header }}</div>
        @endisset
    </div>

    <!-- User menu -->
    <x-dropdown align="right" width="48">
        <x-slot name="trigger">
            <button class="inline-flex items-center gap-2 px-2 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 focus:outline-none transition">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-semibold">
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="hidden sm:block">{{ Auth::user()->name }}</span>
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
        </x-slot>

        <x-slot name="content">
            <div class="px-4 py-2 border-b border-gray-100">
                <div class="text-sm font-medium text-gray-800">{{ Auth::user()->name }}</div>
                <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
            </div>

            <x-dropdown-link :href="route('profile.edit')">
                {{ __('Profile') }}
            </x-dropdown-link>

            <!-- Authentication -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</header>
