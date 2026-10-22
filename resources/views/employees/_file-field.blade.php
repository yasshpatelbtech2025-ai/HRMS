{{-- An employee document upload with a link to the current file. Expects: $e, $field, $label; optional $required. --}}
<div>
    <x-field :name="$field" type="file" :label="__($label)" :required="$required ?? false" accept=".pdf,.jpg,.jpeg,.png" :hint="__('PDF, JPG or PNG, up to 5 MB.')" />
    @if ($e->{$field.'_path'})
        <a href="{{ route('employees.file', [$e, $field]) }}" target="_blank" class="text-xs text-brand-600 hover:underline">{{ __('View current file') }}</a>
    @endif
</div>
