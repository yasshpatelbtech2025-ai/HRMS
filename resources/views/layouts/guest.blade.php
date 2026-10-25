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
        @php
            // A decorative register page: what a month looks like in the app. Fixed wording,
            // so it never reads as real data.
            $entries = [
                ['01', __('Muster roll opened'), __('All sites')],
                ['07', __('Salary sheet generated'), __('Payroll')],
                ['15', __('PF and ESIC returns filed'), __('Compliance')],
                ['20', __('Leave requests approved'), __('Attendance')],
                ['25', __('Tax invoices sent'), __('Invoice')],
                ['30', __('Month closed'), __('Reports')],
            ];
        @endphp
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,6fr)_minmax(0,5fr)]">
            {{-- Brand panel: a ruled register page; its lines rule themselves in once. --}}
            <aside class="relative hidden overflow-hidden border-e border-line bg-surface lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16" aria-hidden="true">
                <x-brand-logo size="lg" />

                <div class="max-w-xl">
                    <p class="text-4xl font-semibold text-ink">{{ __('Attendance, payroll and compliance for every client site.') }}</p>
                    <p class="mt-4 max-w-md text-base text-ink-2">{{ __('One register for every employee, every company and every month.') }}</p>

                    <div class="relative mt-12 ps-16">
                        <span class="register-line absolute inset-y-0 start-12 w-0.5 origin-top rounded-full bg-primary" style="--i: 0"></span>
                        <p class="register-line mb-2 flex justify-between font-mono text-xs text-ink-3" style="--i: 0"><span>{{ now()->format('F Y') }}</span><span>{{ __('Register') }}</span></p>
                        <ol>
                            @foreach ($entries as $i => [$day, $what, $where])
                                <li class="register-line relative flex items-baseline gap-4 border-t border-line py-3 text-sm" style="--i: {{ $i + 1 }}">
                                    <span class="absolute -start-16 w-10 text-end font-mono text-xs text-ink-3">{{ $day }}</span>
                                    <span class="flex-1 text-ink">{{ $what }}</span>
                                    <span class="text-13 text-ink-3">{{ $where }}</span>
                                </li>
                            @endforeach
                            <li class="register-line border-t border-line" style="--i: 7"></li>
                        </ol>
                    </div>
                </div>

                <p class="flex items-center justify-between font-mono text-xs text-ink-3">
                    <span>{{ now()->format('d M Y') }}</span>
                    <span>{{ __('Bharuch · Mumbai') }}</span>
                </p>
            </aside>

            <main class="flex flex-col items-center justify-center px-4 py-10 sm:px-10">
                <div class="w-full max-w-sm">
                    <a href="/" class="mb-10 inline-block lg:hidden"><x-brand-logo /></a>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
