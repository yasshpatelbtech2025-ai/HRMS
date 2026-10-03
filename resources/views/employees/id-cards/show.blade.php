<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    @php($e = $employee)

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('employees.id-cards') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to id card list') }}</a>
        <div class="flex items-center gap-2">
            @if ($e->id_card_assigned_on)
                <span class="text-sm text-gray-600">{{ __('Assigned on :date', ['date' => $e->id_card_assigned_on->format('d M Y')]) }}</span>
            @else
                <form method="POST" action="{{ route('employees.id-cards.assign', $e) }}">
                    @csrf
                    <button class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Mark as assigned today') }}</button>
                </form>
            @endif
            <button type="button" onclick="window.print()" class="rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Print Id Card') }}</button>
        </div>
    </div>

    {{-- Front and back in the design chosen in Company › ID Card Formats. --}}
    @include('employees.id-cards._card-styles')
    <div class="id-card-print flex flex-wrap items-start justify-center gap-6">
        <div class="text-center">
            {{ \App\Support\IdCardFormats::render($format, 'front', $values) }}
            <p class="mt-2 text-xs text-gray-500 print:hidden">{{ __('Front') }}</p>
        </div>
        <div class="text-center">
            {{ \App\Support\IdCardFormats::render($format, 'back', $values) }}
            <p class="mt-2 text-xs text-gray-500 print:hidden">{{ __('Back') }}</p>
        </div>
    </div>
    <p class="mt-4 text-center text-xs text-gray-500 print:hidden">
        {{ __('Design :id.', ['id' => $format['id']]) }} <a href="{{ route('company.id-card-formats') }}" class="text-brand-600 hover:underline">{{ __('Change the design') }}</a>
    </p>
</x-app-layout>
