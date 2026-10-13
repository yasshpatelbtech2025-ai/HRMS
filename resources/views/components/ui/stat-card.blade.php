{{--
    Headline number that links to the list behind it.
    pending=true: the module that feeds this number isn't built yet, so show "—" and say so.
--}}
@props(['label', 'value' => null, 'href' => null, 'hint' => null, 'pending' => false])
@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'group block rounded-card border p-4 transition-colors',
        'border-line bg-surface shadow-hairline' => ! $pending,
        'border-dashed border-line-strong bg-transparent' => $pending,
        'hover:border-primary/40 hover:bg-primary-soft/30' => $href,
    ]) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-13 font-medium text-ink-2">{{ $label }}</p>
        @if ($href)<x-icon name="arrow-right" class="size-4 text-ink-3 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100" />@endif
    </div>
    <p class="mt-2 font-mono text-2xl font-medium tabular {{ $pending ? 'text-ink-3' : 'text-ink' }}">{{ $pending ? '—' : $value }}</p>
    @if ($pending)
        <p class="mt-1 text-xs text-ink-3">{{ __('Not connected yet') }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-ink-3">{{ $hint }}</p>
    @endif
</{{ $tag }}>
