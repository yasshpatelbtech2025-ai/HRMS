@props(['active'])

<span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $active ? 'bg-success-soft text-success ring-success/20' : 'bg-sunken text-ink-2 ring-line' }}">
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $active ? __('Active') : __('Inactive') }}
</span>
