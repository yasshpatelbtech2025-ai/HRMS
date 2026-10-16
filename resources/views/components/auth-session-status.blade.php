@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md bg-success-soft px-3 py-2 text-sm text-success']) }}>
        {{ $status }}
    </div>
@endif
