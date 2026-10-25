{{--
    The bar at the bottom of a long form that stays in view while you scroll:
    a status line on the left (slot `status`), the buttons on the right (default slot).
--}}
<div {{ $attributes->class(['sticky bottom-0 z-20 -mx-4 mt-8 border-t border-line bg-surface/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-surface/85 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 print:hidden']) }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0 text-13 text-ink-3">{{ $status ?? '' }}</div>
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    </div>
</div>
