{{--
    Address inputs for one address type, bound to employeeForm's
    `addresses[type]`. Expects: $type, $readonly (an Alpine expression).
--}}
@php
    $fields = [
        'address_line_1' => ['Address Line 1', null],
        'address_line_2' => ['Address Line 2', null],
        'city' => ['City', 'employee-cities'],
        'village' => ['Village', null],
        'taluka' => ['Taluka', 'employee-talukas'],
        'district' => ['District', 'employee-districts'],
        'state' => ['State', 'employee-states'],
        'country' => ['Country', 'employee-countries'],
    ];
@endphp

<div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
    @foreach ($fields as $field => [$label, $list])
        <div>
            <label for="address-{{ $type }}-{{ $field }}" class="control-label">{{ __($label) }}</label>
            <input id="address-{{ $type }}-{{ $field }}" name="addresses[{{ $type }}][{{ $field }}]" x-model="addresses.{{ $type }}.{{ $field }}"
                   @if ($list) list="{{ $list }}" @endif :readonly="{{ $readonly }}" autocomplete="off" placeholder="{{ __($label) }}"
                   class="control px-3 read-only:bg-sunken read-only:text-ink-2">
            <x-input-error class="mt-1.5" :messages="$errors->get('addresses.'.$type.'.'.$field)" />
        </div>
    @endforeach
</div>
