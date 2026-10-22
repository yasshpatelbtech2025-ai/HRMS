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

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($fields as $field => [$label, $list])
        <div>
            <label for="address-{{ $type }}-{{ $field }}" class="block text-sm font-medium text-gray-700">{{ __($label) }}</label>
            <input id="address-{{ $type }}-{{ $field }}" name="addresses[{{ $type }}][{{ $field }}]" x-model="addresses.{{ $type }}.{{ $field }}"
                   @if ($list) list="{{ $list }}" @endif :readonly="{{ $readonly }}" autocomplete="off" placeholder="{{ __($label) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm read-only:bg-gray-100 focus:border-brand-500 focus:ring-brand-500">
            <x-input-error class="mt-1" :messages="$errors->get('addresses.'.$type.'.'.$field)" />
        </div>
    @endforeach
</div>
