{{--
    A row of summary figures above a list, joined into one ruled strip. Each item:
    label, value, optional hint, optional href (the figure becomes a filter link) and
    `active` to mark the filter that is applied.
    tone: default | success | warn | danger colours the number.
--}}
@props(['items'])

<div class="card mb-5 grid grid-cols-2 overflow-hidden {{ count($items) > 4 ? 'sm:grid-cols-3 lg:grid-cols-5' : 'lg:grid-cols-4' }} print:hidden">
    @foreach ($items as $item)
        @php
            $tone = ['success' => 'text-success', 'warn' => 'text-warn', 'danger' => 'text-danger'][$item['tone'] ?? ''] ?? 'text-ink';
            $active = $item['active'] ?? false;
            $classes = 'relative -mb-px -me-px block min-w-0 border-b border-e border-line px-4 py-3.5 '
                .($active ? 'bg-primary-soft/60 before:absolute before:inset-y-3 before:start-0 before:w-0.5 before:rounded-full before:bg-primary' : '')
                .(isset($item['href']) ? ' hover:bg-sunken/70' : '');
        @endphp
        @if (isset($item['href']))
            <a href="{{ $item['href'] }}" class="{{ $classes }}" @if ($active) aria-current="true" @endif>
        @else
            <div class="{{ $classes }}">
        @endif
            <span class="block truncate text-13 {{ $active ? 'font-medium text-primary-ink' : 'text-ink-2' }}">{{ $item['label'] }}</span>
            <span class="mt-1 block font-mono text-xl font-medium tabular {{ $tone }}">{{ $item['value'] }}</span>
            @isset($item['hint'])<span class="mt-0.5 block truncate text-xs text-ink-3">{{ $item['hint'] }}</span>@endisset
        @if (isset($item['href']))
            </a>
        @else
            </div>
        @endif
    @endforeach
</div>
