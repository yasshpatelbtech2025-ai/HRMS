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
            // A decorative muster roll: one row per shift, one square per day. P present,
            // L leave, A absent, W week off. Fixed pattern so it never looks like real data.
            $roll = [
                'General' => 'PPPPPPWPPPLPPW',
                'Shift A' => 'PPAPPPWPPPPPPW',
                'Shift B' => 'PPPPLLWPPPPAPW',
                'Night'   => 'PPPPPPWPAPPPPW',
                'Boiler'  => 'PLPPPPWPPPPPPW',
            ];
            $cell = ['P' => 'bg-[#2fa36b]', 'L' => 'bg-[#e0a43a]', 'A' => 'bg-srympex', 'W' => 'bg-white/15'];
            $today = 12;
        @endphp
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,6fr)_minmax(0,5fr)]">
            {{-- Brand panel: Site Ink, the Srympex wordmark and a muster roll filling in. --}}
            <aside class="relative hidden overflow-hidden bg-[#17181C] text-white lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16" aria-hidden="true">
                <x-brand-logo size="lg" invert />

                <div class="max-w-xl">
                    <p class="text-[34px] font-semibold leading-[1.15] tracking-tight">{{ __('Every employee, every company, every month: in one place.') }}</p>
                    <p class="mt-4 max-w-md text-base text-white/60">{{ __('Attendance, compliance and payroll for your client sites.') }}</p>

                    <div class="mt-10 rounded-xl border border-white/10 bg-white/[0.04] p-5">
                        <div class="mb-3 flex items-center justify-between font-mono text-[11px] text-white/45">
                            <span>{{ __('Muster roll') }}</span>
                            <span>{{ now()->format('M Y') }}</span>
                        </div>
                        <div class="grid grid-cols-[4.5rem_repeat(14,minmax(0,1fr))] items-center gap-[5px]">
                            <span></span>
                            @for ($d = 1; $d <= 14; $d++)
                                <span class="text-center font-mono text-[10px] {{ $d === $today ? 'text-white' : 'text-white/35' }}">{{ $d }}</span>
                            @endfor
                            @foreach ($roll as $label => $days)
                                <span class="truncate font-mono text-[11px] text-white/55">{{ $label }}</span>
                                @foreach (str_split($days) as $i => $state)
                                    <span class="muster-cell aspect-square rounded-[3px] {{ $cell[$state] }} {{ $i + 1 === $today ? 'ring-2 ring-white/70 ring-offset-1 ring-offset-[#17181C]' : '' }}"
                                          style="--i: {{ $i }}"></span>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                <p class="flex items-center justify-between font-mono text-xs text-white/45">
                    <span>{{ now()->format('d M Y') }}</span>
                    <span>{{ __('Bharuch · Mumbai') }}</span>
                </p>
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
