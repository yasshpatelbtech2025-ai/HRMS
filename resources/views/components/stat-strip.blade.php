{{--
    A row of summary cards above a list. Each item: label, value, optional hint, optional href
    (the card becomes a filter link) and `active` to mark the filter that is applied.
    tone: default | success | warn | danger colours the number.
--}}
@props(['items'])

<div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 print:hidden">
    @foreach ($items as $item)
        @php
            $tone = ['success' => 'text-success', 'warn' => 'text-warn', 'danger' => 'text-danger'][$item['tone'] ?? ''] ?? 'text-ink';
            $classes = 'block rounded-lg border bg-surface px-4 py-3 shadow-hairline '
                .(($item['active'] ?? false) ? 'border-primary ring-1 ring-primary' : 'border-line')
                .(isset($item['href']) ? ' transition hover:border-line-strong hover:bg-sunken' : '');
        @endphp
        @if (isset($item['href']))
            <a href="{{ $item['href'] }}" class="{{ $classes }}" @if ($item['active'] ?? false) aria-current="true" @endif>
        @else
            <div class="{{ $classes }}">
        @endif
            <span class="block text-xs font-medium text-ink-3">{{ $item['label'] }}</span>
            <span class="mt-1 block text-2xl font-semibold tabular {{ $tone }}">{{ $item['value'] }}</span>
            @isset($item['hint'])<span class="mt-0.5 block text-xs text-ink-3">{{ $item['hint'] }}</span>@endisset
        @if (isset($item['href']))
            </a>
        @else
            </div>
        @endif
    @endforeach
</div>
