<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken disabled:pointer-events-none disabled:opacity-50']) }}>
    {{ $slot }}
</button>
