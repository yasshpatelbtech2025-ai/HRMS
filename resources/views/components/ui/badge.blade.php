{{-- A small status label. tone: neutral | primary | success | warn | danger | info. `dot` adds a leading dot. --}}
@props(['tone' => 'neutral', 'dot' => false])
@php
    $tones = [
        'neutral' => 'bg-sunken text-ink-2',
        'primary' => 'bg-primary-soft text-primary-ink',
        'success' => 'bg-success-soft text-success',
        'warn' => 'bg-warn-soft text-warn',
        'danger' => 'bg-danger-soft text-danger',
        'info' => 'bg-info-soft text-info',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap rounded px-1.5 py-0.5 text-xs font-medium', $tones[$tone] ?? $tones['neutral']]) }}>
    @if ($dot)<span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
