{{-- CSV / Excel / PDF / Print buttons for a report; exports keep the current filters. --}}
@php $btn = 'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50'; @endphp
<div class="flex flex-wrap gap-2 print:hidden">
    @foreach (['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'] as $format => $label)
        <a href="{{ request()->fullUrlWithQuery(['format' => $format]) }}" class="{{ $btn }}">{{ __($label) }}</a>
    @endforeach
    <button type="button" onclick="window.print()" class="{{ $btn }}">{{ __('Print') }}</button>
</div>
