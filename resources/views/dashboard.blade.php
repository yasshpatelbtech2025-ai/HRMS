<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    @php
        // Placeholder values — to be replaced with real database counts.
        $stats = [
            ['label' => __('Total employee'), 'value' => '—', 'accent' => 'bg-indigo-500'],
            ['label' => __('Today presents'), 'value' => '—', 'accent' => 'bg-emerald-500'],
            ['label' => __('Today absents'), 'value' => '—', 'accent' => 'bg-rose-500'],
            ['label' => __('Today leave'), 'value' => '—', 'accent' => 'bg-amber-500'],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="relative overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                <span class="absolute inset-y-0 start-0 w-1 {{ $stat['accent'] }}"></span>
                <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>
</x-app-layout>
