{{--
    A checkbox (or, with `switch`, a toggle) with its label on the right and an
    optional line of help. Pass name/value/checked as usual; extra attributes go
    on the input.
--}}
@props(['label', 'help' => null, 'switch' => false, 'id' => null])
@php $id ??= 'cb-'.\Illuminate\Support\Str::random(6); @endphp
<label for="{{ $id }}" class="group flex cursor-pointer items-start gap-3 text-sm text-ink">
    @if ($switch)
        <span class="relative mt-0.5 inline-flex shrink-0">
            <input id="{{ $id }}" type="checkbox" {{ $attributes->merge(['class' => 'peer sr-only']) }}>
            <span class="h-5 w-9 rounded-full bg-line-strong transition-colors duration-fast peer-checked:bg-primary peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary peer-disabled:opacity-50"></span>
            <span class="absolute start-0.5 top-0.5 size-4 rounded-full bg-surface shadow-md transition-transform duration-fast peer-checked:translate-x-4 rtl:peer-checked:-translate-x-4"></span>
        </span>
    @else
        <input id="{{ $id }}" type="checkbox" {{ $attributes->merge(['class' => 'mt-0.5 size-4 shrink-0 rounded border-field text-primary focus:ring-primary/30']) }}>
    @endif
    <span class="min-w-0">
        <span class="block leading-5">{{ $label }}</span>
        @if ($help)<span class="mt-0.5 block text-xs text-ink-3">{{ $help }}</span>@endif
    </span>
</label>
