{{--
    One group of a form: a short heading and one line of help on the left (on wide
    screens), the fields on the right. `columns` sets the field grid (1-3).
--}}
@props(['title', 'description' => null, 'columns' => 2, 'id' => null])
@php
    $grid = [1 => 'grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 xl:grid-cols-3'][$columns] ?? 'sm:grid-cols-2';
@endphp
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['grid gap-x-10 gap-y-4 border-b border-line py-7 first:pt-0 last:border-b-0 last:pb-0 lg:grid-cols-[15rem_1fr]']) }}>
    <div class="min-w-0">
        <h2 class="text-sm font-semibold text-ink">{{ $title }}</h2>
        @if ($description)<p class="mt-1 max-w-prose text-13 text-ink-2">{{ $description }}</p>@endif
    </div>
    <div class="grid min-w-0 grid-cols-1 gap-x-5 gap-y-5 {{ $grid }}">
        {{ $slot }}
    </div>
</section>
