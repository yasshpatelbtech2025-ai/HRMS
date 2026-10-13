{{-- confirm: ['title' => ..., 'message' => ..., 'confirmLabel' => ..., 'action' => url] opens the shared confirm dialog --}}
@props(['href' => null, 'icon' => null, 'danger' => false, 'confirm' => null])
@php
    $classes = 'flex w-full items-center gap-2 rounded-[5px] px-2.5 py-1.5 text-start text-sm transition-colors focus:outline-none '
        .($danger ? 'text-danger hover:bg-danger-soft focus:bg-danger-soft' : 'text-ink hover:bg-sunken focus:bg-sunken');
@endphp
@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4 {{ $danger ? '' : 'text-ink-3' }}" />@endif{{ $slot }}
    </a>
@else
    <button role="menuitem" @if ($confirm) x-on:click="$dispatch('confirm', {{ Js::from($confirm) }})" @endif {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4 {{ $danger ? '' : 'text-ink-3' }}" />@endif{{ $slot }}
    </button>
@endif
