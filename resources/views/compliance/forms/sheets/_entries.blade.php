{{--
    Rows typed into a register: the saved rows (with delete on screen) and,
    on screen, an "Add Row" / "Save" table for new ones.
    Expects: $entries, $employeeOptions, $columns (key => [label, type]), $form, $company, $month, $printing, $leading (cells before the columns).
--}}
@php $types = config('compliance.recovery_types'); @endphp
@foreach ($entries as $entry)
    <tr>
        @include('compliance.forms.sheets._entry-leading', ['employee' => $entry->employee, 'number' => $loop->iteration + ($offset ?? 0)])
        @foreach ($columns as $key => [$label, $type])
            <td class="{{ $type === 'amount' ? 'money' : '' }}">
                @php $v = $entry->data[$key] ?? ''; @endphp
                {{ $type === 'date' && $v ? \Carbon\Carbon::parse($v)->format('d-m-Y') : ($type === 'amount' && $v !== '' ? number_format((float) $v, 2) : $v) }}
            </td>
        @endforeach
        @unless ($printing)
            <td style="border: none;">
                @can('delete-records')
                    <form method="POST" action="{{ route('compliance.entries.destroy', $entry) }}" data-confirm="{{ __('Delete this row?') }}">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-medium text-red-600 hover:underline">{{ __('Delete') }}</button>
                    </form>
                @endcan
            </td>
        @endunless
    </tr>
@endforeach
