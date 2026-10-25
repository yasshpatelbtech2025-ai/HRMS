{{--
    A list table in a card: header row stays in view while scrolling, wide tables
    scroll inside the card. Slots: `toolbar` (above), default (thead + tbody), `footer`.
--}}
@props(['label' => null])
<div {{ $attributes->class(['card overflow-hidden']) }}>
    {{ $toolbar ?? '' }}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-line text-sm" @if ($label) aria-label="{{ $label }}" @endif>
            {{ $slot }}
        </table>
    </div>
    {{ $footer ?? '' }}
</div>
