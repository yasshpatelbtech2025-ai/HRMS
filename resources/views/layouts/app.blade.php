<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-3 focus:top-3 focus:z-[80] focus:rounded-control focus:bg-surface focus:px-3 focus:py-2 focus:shadow-pop">{{ __('Skip to content') }}</a>

        <div x-data="{ drawer: false, rail: $persist(false).as('sidebar:rail') }"
             x-effect="document.documentElement.classList.toggle('rail', rail)"
             @keydown.escape.window="drawer = false"
             class="min-h-dvh">
            @include('layouts.sidebar')

            <div class="app-main flex min-h-dvh flex-col">
                @include('layouts.topbar')

                <main id="main" tabindex="-1" class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @include('layouts.palette')
        <x-ui.confirm-dialog />
        <x-ui.toast-stack />
    </body>
</html>
