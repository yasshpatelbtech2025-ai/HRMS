{{--
    The product logo from config/branding.php: the Srympex wordmark (or the file in BRAND_LOGO),
    otherwise a letter tile with the name. `compact` shows only the red letter tile (sidebar
    icon rail), `size="lg"` is for the login screen, `invert` for use on the dark login panel.
--}}
@props(['compact' => false, 'size' => 'md', 'invert' => false])

@php
    $logo = config('branding.logo');
    $logo = $logo && is_file(public_path($logo)) ? asset($logo) : null;
    [$tile, $name, $logoHeight] = $size === 'lg'
        ? ['size-11 text-xl rounded-[10px]', 'text-xl', 'h-10']
        : ['size-8 text-[15px] rounded-[7px]', 'text-[15px]', 'h-7'];
    $tagColor = $invert ? 'text-white/60' : 'text-ink-3';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex min-w-0 items-center gap-2.5']) }}>
    @if ($logo && ! $compact)
        <img src="{{ $logo }}" alt="{{ config('branding.name') }}" class="{{ $logoHeight }} w-auto max-w-[10rem] object-contain">
        <span class="min-w-0 border-s {{ $invert ? 'border-white/20' : 'border-line' }} ps-2.5 text-xs leading-tight {{ $tagColor }}">{{ config('branding.tagline') }}</span>
    @else
        <span class="flex {{ $tile }} shrink-0 items-center justify-center bg-srympex font-semibold text-white" aria-hidden="true">{{ config('branding.mark') }}</span>
        @unless ($compact)
            <span class="min-w-0 leading-tight">
                <span class="block truncate {{ $name }} font-semibold {{ $invert ? 'text-white' : 'text-ink' }}">{{ config('branding.name') }}</span>
                <span class="block truncate text-xs {{ $tagColor }}">{{ config('branding.tagline') }}</span>
            </span>
        @else
            <span class="sr-only">{{ config('branding.name') }}</span>
        @endunless
    @endif
</span>
