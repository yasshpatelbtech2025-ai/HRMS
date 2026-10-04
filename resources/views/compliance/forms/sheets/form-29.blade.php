@php
    // Typed-in columns, in the order of the register (the employee is picked first).
    $columns = [
        'notice_at' => ['Date & time of notice', 'datetime-local'], 'esic' => ['ESIC Insurance number', 'text'],
        'date' => ['Date of accident', 'date', true], 'time' => ['Time', 'time'], 'place' => ['Place', 'text'],
        'cause' => ['Cause of accident / dangerous occurrence', 'text'], 'nature' => ['Nature of injury / dangerous occurrence', 'text'],
        'doing' => ['What exactly was the injured person doing at that time', 'text'], 'notice_by' => ['Name of the person giving the notice', 'text'],
        'witnesses' => ['Name, address and occupation of two witnesses', 'text'], 'returned_on' => ['Date of return of injured person to work', 'date'],
        'days_absent' => ['Number of days absent', 'number'],
    ];
    $date = fn ($v, $format = 'd-m-Y') => $v ? \Carbon\Carbon::parse($v)->format($format) : '';
@endphp
<p class="sub">FORM NO. 29</p>
<p class="sub" style="font-weight: 400;">(Prescribed under Rule 111)</p>
<h1>Register of accidents, major accidents and dangerous occurrences</h1>
<p class="sub" style="font-size: 16px;">{{ $company->establishment_name }} &mdash; {{ $year }}</p>
<table class="reg">
    <tr>
        <th rowspan="2">Serial number</th><th rowspan="2">Date &amp; time of notice</th>
        <th rowspan="2">Name and serial number of the person involved in the register of adult/child workers</th>
        <th rowspan="2">ESIC Insurance number</th><th rowspan="2">Date</th><th colspan="5">Injury/dangerous occurrence</th>
        <th rowspan="2">Name of the person giving the notice</th><th rowspan="2">Name, address and occupation of two witnesses</th>
        <th rowspan="2">Date of return of injured person to work</th>
        <th rowspan="2">Number of days the injured person was absent from the work including holidays and off days</th>
        <th rowspan="2">Signature and designation of the person who makes the entry with date</th>
    </tr>
    <tr>
        <th>Time</th><th>Place</th><th>Cause of accident/major accident/dangerous occurrence</th><th>Nature of injury/dangerous occurrence</th>
        <th>What exactly was the injured person, if any, doing at that time</th>
    </tr>
    <tr class="colno">@foreach (range(1, 15) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @forelse ($entries as $entry)
        @php $d = $entry->data; @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="nw">{{ $date($d['notice_at'] ?? null, 'd-m-Y H:i') }}</td>
            <td>{{ $entry->employee->full_name }} ({{ $entry->employee->employee_code }})</td>
            <td>{{ $d['esic'] ?? $entry->employee->ip_number }}</td>
            <td class="nw">{{ $date($d['date'] ?? null) }}</td>
            <td>{{ $d['time'] ?? '' }}</td>
            <td>{{ $d['place'] ?? '' }}</td>
            <td>{{ $d['cause'] ?? '' }}</td>
            <td>{{ $d['nature'] ?? '' }}</td>
            <td>{{ $d['doing'] ?? '' }}</td>
            <td>{{ $d['notice_by'] ?? '' }}</td>
            <td>{{ $d['witnesses'] ?? '' }}</td>
            <td class="nw">{{ $date($d['returned_on'] ?? null) }}</td>
            <td>{{ $d['days_absent'] ?? '' }}</td>
            <td></td>
            @unless ($printing)
                <td style="border: none;">
                    <form method="POST" action="{{ route('compliance.entries.destroy', $entry) }}" data-confirm="{{ __('Delete this accident?') }}">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-medium text-red-600 hover:underline">{{ __('Delete') }}</button>
                    </form>
                </td>
            @endunless
        </tr>
    @empty
        <tr><td colspan="15" class="empty">No accidents recorded in {{ $year }}</td></tr>
    @endforelse
</table>
@unless ($printing)
    <p class="small" style="color: #555;">Use <strong>Add Row</strong> to record an accident. The ESIC number is taken from the employee's profile when left blank, and each accident is filed under the year it happened.</p>
    @include('compliance.forms.sheets._entry-form')
@endunless
