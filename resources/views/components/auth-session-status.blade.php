@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-card border border-success/25 bg-success-soft px-3.5 py-2.5 text-sm text-success']) }}>
        {{ $status }}
    </div>
@endif
