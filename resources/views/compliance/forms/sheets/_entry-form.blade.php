{{-- "Add Row" / "Save" for a typed-in register. Expects: $columns, $form, $company, $month, $employeeOptions, $leading. --}}
@php $types = config('compliance.recovery_types'); @endphp
<form method="POST" action="{{ route('compliance.forms.entries', [$company, $form]) }}" x-data="{ rows: [] }" class="mt-2">
    @csrf
    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
    <table class="reg" x-show="rows.length" style="display: none;">
        <tr>
            <th>{{ __('Employee') }}</th>
            @foreach ($columns as $key => [$label, $type])<th>{{ $label }}</th>@endforeach
            <th></th>
        </tr>
        <template x-for="(row, index) in rows" :key="row.id">
            <tr>
                <td>
                    <select :name="`rows[${index}][employee]`" required>
                        <option value="">{{ __('Select employee') }}</option>
                        @foreach ($employeeOptions as $option)
                            <option value="{{ $option->uuid }}">{{ $option->employee_code }} - {{ $option->full_name }}</option>
                        @endforeach
                    </select>
                </td>
                @foreach ($columns as $key => [$label, $type])
                    <td>
                        @if ($type === 'recovery')
                            <select :name="`rows[${index}][{{ $key }}]`" required>
                                @foreach ($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                            </select>
                        @elseif ($type === 'yesno')
                            <select :name="`rows[${index}][{{ $key }}]`"><option value="">{{ __('Select') }}</option><option>Yes</option><option>No</option></select>
                        @else
                            <input :name="`rows[${index}][{{ $key }}]`" type="{{ in_array($type, ['date', 'number'], true) ? $type : ($type === 'amount' ? 'number' : 'text') }}" @if ($type === 'amount') step="0.01" min="0" @endif>
                        @endif
                    </td>
                @endforeach
                <td><button type="button" x-on:click="rows.splice(index, 1)" class="text-xs font-medium text-red-600">✕</button></td>
            </tr>
        </template>
    </table>
    <div class="flex gap-2">
        <button type="button" x-on:click="rows.push({ id: Date.now() })" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Add Row') }}</button>
        <button x-show="rows.length" style="display: none;" class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ __('Save') }}</button>
    </div>
</form>
