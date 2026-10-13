{{-- Sticky save bar at the bottom of long forms, so Save is always one click away. --}}
@props(['cancel', 'submit' => __('Save')])
<div {{ $attributes->class(['fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface/95 backdrop-blur app-main']) }}>
    <div class="mx-auto flex max-w-[1600px] items-center justify-end gap-2 px-4 py-3 sm:px-6 lg:px-8">
        {{ $slot }}
        <x-ui.button variant="ghost" :href="$cancel">{{ __('Cancel') }}</x-ui.button>
        <x-ui.button>{{ $submit }}</x-ui.button>
    </div>
</div>
