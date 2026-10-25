{{--
    Add or edit a medical examination. One examination fills Form 5, 27-A,
    30 and the Form 32 health register.
    Expects: $modal, $company, $form, $employees, $employee (preselected), $examination (null to add).
--}}
@php
    $mine = old('_modal') === $modal;
    $val = fn (string $key, $current = null) => $mine ? old($key) : ($current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current);
    $input = 'mt-1.5 control';
    $label = 'block text-[15px] font-bold text-gray-700';
    $id = fn (string $key) => $modal.'-'.str_replace(['.', '_'], '-', $key);
@endphp
<form method="POST" enctype="multipart/form-data" class="p-6"
      action="{{ $examination ? route('compliance.examinations.update', $examination) : route('compliance.examinations.store', $company) }}">
    @csrf
    @if ($examination) @method('PUT') @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">
    <input type="hidden" name="form" value="{{ $form }}">

    <h2 class="text-lg font-semibold text-gray-900">{{ $examination ? __('Edit examination') : __('Add examination') }}</h2>
    <p class="mt-1 text-sm text-gray-500">{{ __('Fill in what the certifying surgeon recorded. Form 5, 27-A, 30 and the Form 32 health register are all printed from this examination; leave out what does not apply.') }}</p>

    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="{{ $id('employee') }}" class="{{ $label }}">{{ __('Employee') }} <span class="text-red-600">*</span></label>
            @if ($examination)
                <p class="mt-2 text-sm text-gray-900">{{ $examination->employee->employee_code }} - {{ $examination->employee->full_name }}</p>
            @else
                <select id="{{ $id('employee') }}" name="employee" required class="{{ $input }}">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($employees as $option)
                        <option value="{{ $option->uuid }}" @selected(($mine ? old('employee') : $employee?->uuid) === $option->uuid)>{{ $option->employee_code }} - {{ $option->full_name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <div>
            <label for="{{ $id('examined_on') }}" class="{{ $label }}">{{ __('Examination date') }} <span class="text-red-600">*</span></label>
            <input id="{{ $id('examined_on') }}" type="date" name="examined_on" required value="{{ $val('examined_on', $examination?->examined_on ?? today()) }}" class="{{ $input }}">
        </div>
        <div>
            <label for="{{ $id('result') }}" class="{{ $label }}">{{ __('Result') }}</label>
            <select id="{{ $id('result') }}" name="result" class="{{ $input }}">
                <option value="">{{ __('Not stated') }}</option>
                <option value="fit" @selected($val('result', $examination?->result) === 'fit')>{{ __('Fit') }}</option>
                <option value="unfit" @selected($val('result', $examination?->result) === 'unfit')>{{ __('Unfit') }}</option>
            </select>
        </div>
        <div>
            <label for="{{ $id('certifying_surgeon') }}" class="{{ $label }}">{{ __('Certifying surgeon') }}</label>
            <input id="{{ $id('certifying_surgeon') }}" name="certifying_surgeon" value="{{ $val('certifying_surgeon', $examination?->certifying_surgeon) }}" placeholder="{{ __('Doctor\'s name') }}" class="{{ $input }}">
        </div>
        <div>
            <label for="{{ $id('extended_until') }}" class="{{ $label }}">{{ __('Certificate extended until') }}</label>
            <input id="{{ $id('extended_until') }}" type="date" name="extended_until" value="{{ $val('extended_until', $examination?->extended_until) }}" class="{{ $input }}">
        </div>
        <div class="sm:col-span-2">
            <label for="{{ $id('signs_symptoms') }}" class="{{ $label }}">{{ __('Signs and symptoms observed') }}</label>
            <textarea id="{{ $id('signs_symptoms') }}" name="signs_symptoms" rows="2" placeholder="{{ __('e.g. NIL') }}" class="{{ $input }}">{{ $val('signs_symptoms', $examination?->signs_symptoms) }}</textarea>
        </div>
    </div>

    @foreach (\App\Models\MedicalExamination::DETAILS as $group => $fields)
        <details class="mt-4 rounded-md border border-gray-200" @if ($loop->first || ($examination && collect($fields)->keys()->contains(fn ($k) => $examination->detail($k)))) open @endif>
            <summary class="cursor-pointer select-none px-4 py-2 text-sm font-semibold text-gray-800">{{ __($group) }}</summary>
            <div class="grid gap-4 px-4 pb-4 sm:grid-cols-2">
                @foreach ($fields as $key => [$text, $type])
                    <div>
                        <label for="{{ $id('details.'.$key) }}" class="{{ $label }}">{{ __($text) }}</label>
                        <input id="{{ $id('details.'.$key) }}" type="{{ $type }}" name="details[{{ $key }}]" value="{{ $val('details.'.$key, $examination?->detail($key)) }}" class="{{ $input }}">
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach

    <div class="mt-4">
        <label for="{{ $id('doc') }}" class="{{ $label }}">{{ __('Signed copy (PDF or image)') }}</label>
        <input id="{{ $id('doc') }}" type="file" name="doc" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">
        @if ($examination?->signed_copy_path)
            <p class="mt-1 text-xs text-gray-500">{{ __('Current:') }} <a href="{{ route('compliance.examinations.signed-copy', $examination) }}" target="_blank" class="text-brand-600 hover:underline">{{ $examination->signed_copy_name }}</a> &mdash; {{ __('choosing a file replaces it.') }}</p>
        @endif
    </div>

    @if ($mine)
        <x-input-error class="mt-4" :messages="collect($errors->getMessages())->flatten()->unique()->all()" />
    @endif

    <div class="mt-6 flex justify-end gap-3">
        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
        <x-primary-button>{{ $examination ? __('Update') : __('Save') }}</x-primary-button>
    </div>
</form>
