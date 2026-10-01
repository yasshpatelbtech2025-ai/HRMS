<!-- Mobile backdrop -->
<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
     class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" style="display: none;"></div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 start-0 z-40 w-64 flex flex-col bg-gray-900 text-gray-300 transform transition-transform duration-200 ease-in-out lg:translate-x-0">
    <!-- Brand -->
    <div class="flex items-center gap-3 h-16 px-6 border-b border-gray-800">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="h-8 w-auto fill-current text-white" />
            <span class="text-lg font-semibold text-white">{{ config('app.name', 'HRMS') }}</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <x-slot name="icon">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
            </x-slot>
            {{ __('Dashboard') }}
        </x-sidebar-link>
    </nav>
</aside>
