{{--
    The product logo from config/branding.php: the real logo file when BRAND_LOGO is set,
    otherwise a neutral placeholder tile with the name. `compact` shows only the tile/logo,
    `size="lg"` is for the login screen.
--}}
@props(['compact' => false, 'size' => 'md'])

@php
    $logo = config('branding.logo');
    $logo = $logo && is_file(public_path($logo)) ? asset($logo) : null;
    [$tile, $name, $logoHeight] = $size === 'lg'
        ? ['size-11 text-xl rounded-[9px]', 'text-xl', 'h-11']
        : ['size-8 text-[15px] rounded-[7px]', 'text-[15px]', 'h-8'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex min-w-0 items-center gap-2.5']) }}>
    @if ($logo)
        <img src="{{ $logo }}" alt="{{ config('branding.name') }}" class="{{ $logoHeight }} w-auto max-w-[10rem] object-contain">
    @else
        <span class="flex {{ $tile }} shrink-0 items-center justify-center bg-primary font-mono font-medium text-white shadow-hairline" aria-hidden="true">{{ config('branding.mark') }}</span>
        @unless ($compact)
            <span class="min-w-0 leading-tight">
                <span class="block truncate {{ $name }} font-semibold text-ink">{{ config('branding.name') }}</span>
                <span class="block truncate text-xs text-ink-3">{{ config('branding.tagline') }}</span>
            </span>
        @endunless
    @endif
</span>
