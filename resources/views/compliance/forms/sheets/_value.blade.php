{{-- A fill-in field on a return: an input on screen, its saved value when printed. Expects: $values, $key; optional $type. --}}
@if ($printing)
    @php $v = $values->value($key); @endphp
    {{ ($type ?? 'text') === 'date' && $v ? \Carbon\Carbon::parse($v)->format('d-m-Y') : $v }}
@else
    <input type="{{ $type ?? 'text' }}" name="raw_data[{{ $key }}]" value="{{ old('raw_data.'.$key, $values->value($key)) }}" @if (($type ?? 'text') === 'number') step="any" @endif style="min-width: {{ $width ?? 140 }}px;">
@endif
