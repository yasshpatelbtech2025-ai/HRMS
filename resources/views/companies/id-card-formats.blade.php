@use('App\Support\IdCardFormats')
<x-app-layout>
    <x-slot name="header">{{ __('Company') }}</x-slot>

    <x-section-tabs section="company" />

    @include('employees.id-cards._card-styles')

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('ID Card Format List') }}</h1>
                <p class="text-sm text-gray-500">{{ __('Pick the design employee ID cards are printed in. Hover over a card to see its back.') }}</p>
            </div>
            <a href="{{ route('employees.id-cards') }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Employee ID cards') }}</a>
        </div>
        <div class="card">

        @if (! $firm->logo_path || ! ($firm->stamp_signature_path || $firm->signature_path || $firm->stamp_path))
            <div class="mx-5 mt-4 rounded-md bg-info-soft px-4 py-3 text-sm text-info ring-1 ring-info/30">
                {{ __('Cards show the firm\'s name, address, logo and the issuing authority\'s stamp and signature. Add the logo and stamp / signature in') }}
                <a href="{{ route('company.firm') }}" class="font-semibold underline">{{ __('Company › Firm') }}</a>.
            </div>
        @endif

        {{-- The chosen design. --}}
        <div class="border-b border-gray-200 px-5 py-6">
            <div class="inline-flex flex-col items-center gap-4 rounded-lg p-4 ring-2 ring-brand-500">
                <div @class(['flip-card', 'landscape' => $selected['orientation'] === 'landscape'])>
                    <div class="flip-card-inner">
                        <div class="flip-card-front">{{ IdCardFormats::render($selected, 'front', $sample) }}</div>
                        <div class="flip-card-back">{{ IdCardFormats::render($selected, 'back', $sample) }}</div>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white">✓ {{ __('Selected') }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-start justify-center gap-6 px-5 py-6">
            @foreach ($formats as $format)
                <div class="flex max-w-full flex-col items-center gap-4 overflow-x-auto rounded-card border border-line p-4">
                    <div @class(['flip-card', 'landscape' => $format['orientation'] === 'landscape'])>
                        <div class="flip-card-inner">
                            <div class="flip-card-front">{{ IdCardFormats::render($format, 'front', $sample) }}</div>
                            <div class="flip-card-back">{{ IdCardFormats::render($format, 'back', $sample) }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('company.id-card-formats.select') }}">
                        @csrf
                        <input type="hidden" name="format" value="{{ $format['id'] }}">
                        <button class="rounded-full border border-brand-300 px-5 py-1.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">{{ __('Select') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
    </div>
</x-app-layout>
