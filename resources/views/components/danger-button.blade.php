<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-3.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:opacity-50 bg-danger text-white shadow-hairline hover:bg-danger-hover']) }}>
    {{ $slot }}
</button>
