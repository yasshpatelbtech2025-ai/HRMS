{{--
    Headline number that links to the list behind it.
    pending=true: the module that feeds this number isn't built yet, so show "—" and say so.
    tone colours the number: success | warn | danger.
--}}
@props(['label', 'value' => null, 'href' => null, 'hint' => null, 'pending' => false, 'tone' => null])
@php
    $tag = $href ? 'a' : 'div';
    $color = ['success' => 'text-success', 'warn' => 'text-warn', 'danger' => 'text-danger'][$tone] ?? 'text-ink';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'group block min-w-0 rounded-card border px-4 py-3.5',
        'border-line bg-surface' => ! $pending,
        'border-dashed border-line-strong bg-transparent' => $pending,
        'hover:border-primary/50 focus-visible:border-primary' => $href,
    ]) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-13 text-ink-2">{{ $label }}</p>
        @if ($href)<x-icon name="arrow-right" class="size-4 shrink-0 text-ink-3 transition duration-fast group-hover:translate-x-0.5 group-hover:text-primary" />@endif
    </div>
    <p class="mt-1.5 font-mono text-2xl font-medium tabular {{ $pending ? 'text-ink-3' : $color }}">{{ $pending ? '—' : $value }}</p>
    @if ($pending)
        <p class="mt-0.5 text-xs text-ink-3">{{ __('Not connected yet') }}</p>
    @elseif ($hint)
        <p class="mt-0.5 truncate text-xs text-ink-3">{{ $hint }}</p>
    @endif
</{{ $tag }}>
