{{-- Signed copy upload and Save for a return (screen only). Expects: $values, $company, $form, $period; optional $label. --}}
@unless ($printing)
    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #ddd;">
        <label style="display: block; font-weight: 600; margin-bottom: 4px;">{{ $label ?? __('Signed copy') }}</label>
        <div class="flex flex-wrap items-center gap-3">
            <input type="file" name="doc" accept=".pdf,.jpg,.jpeg,.png" style="width: auto; border: none;">
            @if ($values->signed_copy_path)
                <a href="{{ route('compliance.forms.signed-copy', [$company, $values->form, $period]) }}" target="_blank" class="text-sm text-brand-600 hover:underline">{{ $values->signed_copy_name }}</a>
            @endif
            <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Save') }}</button>
        </div>
    </div>
@endunless
