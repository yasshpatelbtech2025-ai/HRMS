{{--
    Filters above a list, as one GET form. Each control in the slot changes the list.
    `action` is the list's URL; `clear` shows "Clear filters" when any filter is set.
--}}
@props(['action', 'clear' => null])
<form method="GET" action="{{ $action }}" role="search"
      {{ $attributes->class(['flex flex-wrap items-end gap-3 border-b border-line px-4 py-3 print:hidden [&_select]:min-w-[9rem] max-sm:[&>*]:w-full']) }}>
    {{ $slot }}
    @if ($clear)
        <a href="{{ $clear }}" class="inline-flex h-control items-center rounded-control px-2 text-13 font-medium text-ink-2 hover:text-ink">{{ __('Clear filters') }}</a>
    @endif
</form>
