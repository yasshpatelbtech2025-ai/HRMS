@props(['icon' => 'inbox', 'title', 'message' => null])
<div {{ $attributes->class(['flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="flex size-11 items-center justify-center rounded-full bg-sunken text-ink-3">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <h3 class="mt-3 text-sm font-semibold text-ink">{{ $title }}</h3>
    @if ($message)<p class="mt-1 max-w-sm text-13 text-ink-3">{{ $message }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-4 flex flex-wrap justify-center gap-2">{{ $slot }}</div>@endif
</div>
