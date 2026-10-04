<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'SRYMPEX') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@300..800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- Shown while the next page loads (resources/js/loading.js). --}}
        <div id="page-progress" class="hidden fixed inset-x-0 top-0 z-[70] h-0.5 overflow-hidden bg-brand-100 print:hidden" aria-hidden="true">
            <div class="h-full w-2/5 animate-progress-indeterminate rounded-full bg-brand-500"></div>
        </div>
        <x-toasts />
        <x-confirm-dialog />

        <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-screen">
            @include('layouts.sidebar')

            <div class="lg:ps-[260px] print:ps-0 flex flex-col min-h-screen">
                @include('layouts.topbar')

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
