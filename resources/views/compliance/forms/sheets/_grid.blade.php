{{-- One cell per day of the month with the attendance status (A shown as LOP). Expects: $days. --}}
@foreach ($days as $d)
    <td class="day">{{ $d['status'] === 'A' ? 'LOP' : ($d['status'] ?? '') }}</td>
@endforeach
