{{-- Neutral logo tile + product name from config/branding.php --}}
@props(['compact' => false])
<span {{ $attributes->class(['flex items-center gap-2.5']) }}>
    <span class="flex size-8 shrink-0 items-center justify-center rounded-[7px] bg-primary font-mono text-[15px] font-medium text-white shadow-hairline" aria-hidden="true">
        {{ config('branding.mark') }}
    </span>
    @unless ($compact)
        <span class="min-w-0 leading-tight">
            <span class="block truncate text-[15px] font-semibold text-ink">{{ config('branding.name') }}</span>
            <span class="block truncate text-xs text-ink-3">{{ config('branding.tagline') }}</span>
        </span>
    @endunless
</span>
