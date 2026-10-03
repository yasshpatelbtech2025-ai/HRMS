{{--
    A Maternity Benefit Act form: a numbered list of answers for the year.
    Expects: $heading, $caption (optional), $rows as [number, label, source] where source is
    a fill-in key (string), ['auto', value] (from the records) or null (a heading line).
--}}
@php
    $period = $year.'-12';
    $value = fn (string $key) => view('compliance.forms.sheets._value', ['values' => $values, 'key' => $key, 'width' => 260, 'printing' => $printing])->render();
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<div class="cert">
    <h1>{{ $heading }}</h1>
    <p class="sub">(See rule 16)</p>
    <p class="sub">MATERNITY BENEFIT ACT</p>
    @isset ($caption)<p style="text-align: center;">{{ $caption }}</p>@endisset
    <br>
    <table class="fields">
        @foreach ($rows as [$number, $label, $source])
            <tr>
                <td class="no">{{ $number }}</td>
                <td class="label" @if ($source === null) colspan="3" style="font-weight: 600;" @endif>{{ $label }}</td>
                @if ($source !== null)
                    <td class="sep">:</td>
                    <td>
                        @if (is_array($source))
                            <strong>{{ $source[1] }}</strong>
                        @else
                            {!! $value($source) !!}
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
    </table>
    <div class="signs">
        <div>Date: {{ today()->format('jS M Y') }}</div>
        <div>{{ $signature ?? 'Signature of employer' }}</div>
    </div>
</div>
@unless ($printing)
    @if (isset($auto) && $auto)
        <p class="small" style="color: #555;">The counts in bold come from the employee and attendance records for {{ $year }}; the rest are typed in and saved for the year.</p>
    @endif
    @include('compliance.forms.sheets._save', ['period' => $period])
</form>
@endunless
