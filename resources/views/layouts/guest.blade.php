<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('branding.name') }}</title>

        {{-- Apply the saved (or system) theme before first paint (no flash). --}}
        <script>try { var t = localStorage.getItem('theme'); if (t === 'dark' || (t !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-ink antialiased">
        <x-toasts />
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            {{-- Brand panel, ruled like a register book. --}}
            <aside class="relative hidden overflow-hidden border-e border-line bg-rail lg:flex lg:flex-col lg:justify-between lg:p-12" aria-hidden="true">
                <div class="pointer-events-none absolute inset-0 opacity-70" style="background-image: repeating-linear-gradient(to bottom, transparent 0 39px, rgb(var(--line)) 39px 40px); mask-image: linear-gradient(to bottom, black 40%, transparent);"></div>
                <div class="pointer-events-none absolute inset-y-0 start-24 w-px bg-danger/20"></div>
                <x-brand-logo class="relative" />
                <div class="relative max-w-sm ps-16">
                    <p class="text-2xl font-medium leading-snug text-ink">{{ __('Every employee, every company, every month: in one place.') }}</p>
                    <p class="mt-3 text-sm text-ink-3">{{ __('Attendance, compliance and payroll for your client sites.') }}</p>
                </div>
                <p class="relative ps-16 font-mono text-xs text-ink-3">{{ now()->format('d M Y') }}</p>
            </aside>

            <main class="flex flex-col items-center justify-center px-5 py-10 sm:px-10">
                <div class="w-full max-w-sm">
                    <a href="/" class="mb-10 inline-block lg:hidden"><x-brand-logo /></a>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
