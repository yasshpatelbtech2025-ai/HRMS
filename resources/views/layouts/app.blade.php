<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('branding.name') }}</title>
        {{-- Apply the remembered sidebar width before first paint (no jump). --}}
        <script>try { if (localStorage.getItem('_x_sidebar:rail') === 'true') document.documentElement.classList.add('rail'); } catch (e) {}</script>

        {{-- Apply the saved (or system) theme before first paint (no flash). --}}
        <script>try { var t = localStorage.getItem('theme'); if (t === 'dark' || (t !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}</script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-ink antialiased">
        {{-- Shown while the next page loads (resources/js/loading.js). --}}
        <div id="page-progress" class="hidden fixed inset-x-0 top-0 z-[70] h-0.5 overflow-hidden bg-primary-soft print:hidden" aria-hidden="true">
            <div class="h-full w-2/5 animate-progress-indeterminate rounded-full bg-primary"></div>
        </div>
        <x-toasts />
        <x-confirm-dialog />

        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-3 focus:top-3 focus:z-[80] focus:rounded-md focus:bg-surface focus:px-3 focus:py-2 focus:shadow-pop">{{ __('Skip to content') }}</a>

        <div x-data="{ drawer: false, rail: $persist(false).as('sidebar:rail') }"
             x-effect="document.documentElement.classList.toggle('rail', rail)"
             @keydown.escape.window="drawer = false" class="min-h-screen">
            @include('layouts.sidebar')

            <div class="app-main flex min-h-screen flex-col">
                @include('layouts.topbar')

                <!-- Page Content -->
                <main id="main" tabindex="-1" class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
