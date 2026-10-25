{{-- CSV / Excel / PDF / Print buttons for a report; exports keep the current filters. --}}
@php $btn = 'inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken'; @endphp
<div class="flex flex-wrap gap-2 print:hidden">
    @foreach (['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'] as $format => $label)
        <a href="{{ request()->fullUrlWithQuery(['format' => $format]) }}" class="{{ $btn }}">{{ __($label) }}</a>
    @endforeach
    <button type="button" onclick="window.print()" class="{{ $btn }}">{{ __('Print') }}</button>
</div>
