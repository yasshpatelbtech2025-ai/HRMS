@props(['tone' => 'neutral', 'dot' => false])
@php
    $tones = [
        'neutral' => 'bg-sunken text-ink-2 ring-line',
        'primary' => 'bg-primary-soft text-primary-ink ring-primary/20',
        'success' => 'bg-success-soft text-success ring-success/20',
        'warn' => 'bg-warn-soft text-warn ring-warn/20',
        'danger' => 'bg-danger-soft text-danger ring-danger/20',
        'info' => 'bg-info-soft text-info ring-info/20',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', $tones[$tone]]) }}>
    @if ($dot)<span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
