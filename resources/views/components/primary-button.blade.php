<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover disabled:pointer-events-none disabled:opacity-50']) }}>
    {{ $slot }}
</button>
