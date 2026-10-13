@props(['name', 'id' => null, 'value' => null, 'type' => 'text', 'prefix' => null, 'mono' => false, 'bag' => 'default'])
@php
    $id ??= $name;
    $invalid = $errors->getBag($bag)->has($name);
    $value = old($name, $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : $value);
@endphp

@if ($prefix)
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-ink-3">{{ $prefix }}</span>
@endif
<input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}"
       @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
       {{ $attributes->class(['control', 'ps-7' => $prefix, 'font-mono tracking-wide' => $mono]) }}>
@if ($prefix)
    </div>
@endif
