@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line-strong bg-surface text-ink placeholder:text-ink-3 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-md shadow-none']) }}>
