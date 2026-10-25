{{--
    A labelled form input with help and its validation error. `name` uses dot
    notation for nested fields (e.g. "addresses.present.village"); the submitted
    name and old input follow from it. Phone, PAN, Aadhaar, IFSC, PIN, UAN and
    account fields get an example placeholder here and typing help from
    resources/js/ui/masks.js (matched by name, so the saved value is unchanged).
--}}
@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => null, 'hint' => null, 'help' => null, 'optional' => false, 'placeholder' => null])

@php
    $id = 'field-'.str_replace(['.', '_'], '-', $name);
    $segments = explode('.', $name);
    $inputName = array_shift($segments).collect($segments)->map(fn ($s) => "[{$s}]")->implode('');
    $current = old($name, $value);
    $help ??= $hint;
    $invalid = $errors->has($name);
    $last = \Illuminate\Support\Str::afterLast($name, '.');
    $examples = [
        'email' => 'name@example.com', 'phone' => '98765 43210', 'mobile' => '98765 43210', 'alternate_phone' => '98765 43210',
        'pan_card_no' => 'ABCDE1234F', 'pan' => 'ABCDE1234F', 'aadhar_card_no' => '1234 5678 9012', 'ifsc_code' => 'SBIN0001234',
        'pin_code' => '380001', 'uan_code' => '12 digits', 'micr_code' => '9 digits', 'account_number' => '6 to 20 digits',
    ];
    $placeholder ??= $type === 'email' ? $examples['email'] : ($examples[$last] ?? null);
@endphp

<x-ui.field :label="$label" :for="$id" :name="$name" :required="$required" :help="$help" :optional="$optional">
    <input id="{{ $id }}" name="{{ $inputName }}" type="{{ $type }}" @if ($type !== 'file') value="{{ $current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current }}" @endif
           @if ($required) required @endif @if ($placeholder && $type !== 'file') placeholder="{{ $placeholder }}" @endif
           @if ($help) aria-describedby="{{ $id }}-help" @endif @if ($invalid) aria-invalid="true" @endif
           {{ $attributes->merge(['class' => $type === 'file'
               ? 'block w-full text-sm text-ink-2 file:me-3 file:h-control-sm file:cursor-pointer file:rounded-control file:border file:border-line-strong file:bg-surface file:px-3 file:text-13 file:font-medium file:text-ink hover:file:bg-sunken'
               : 'control px-3']) }}>
</x-ui.field>
