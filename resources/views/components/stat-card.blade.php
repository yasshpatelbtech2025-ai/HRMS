@props(['label', 'value', 'tone' => 'brand'])

@php
$tones = [
    'brand' => 'bg-brand-100 text-brand-700',
    'amber' => 'bg-amber-100 text-amber-700',
    'teal' => 'bg-teal-100 text-teal-700',
    'slate' => 'bg-slate-200 text-slate-700',
    'red' => 'bg-red-100 text-red-700',
    'orange' => 'bg-orange-100 text-orange-700',
];
@endphp

<div class="flex items-center justify-between rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
    <div>
        <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ $value }}</p>
    </div>
    @isset($icon)
        <span class="flex h-12 w-12 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['brand'] }}">{{ $icon }}</span>
    @endisset
</div>
