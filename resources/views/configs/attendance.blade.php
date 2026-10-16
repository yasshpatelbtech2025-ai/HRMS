<x-app-layout>
    <x-slot name="header">{{ __('Configs') }}</x-slot>

    <x-section-tabs section="configs" />

    @php
        $select = 'block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
        $input = 'block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    @endphp

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Attendance setting') }}</h2>
            <form method="GET" action="{{ route('configs.index') }}">
                <label for="settings-company" class="sr-only">{{ __('Company') }}</label>
                <select id="settings-company" name="company" onchange="this.form.submit()" class="{{ $select }} min-w-56">
                    @foreach ($companies as $option)
                        <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">
                {{ __('Add a company first.') }} <a href="{{ route('company.index') }}" class="text-brand-600 hover:underline">{{ __('Go to Company') }}</a>
            </p>
        @else
            <form method="POST" action="{{ route('configs.attendance.update', $company) }}" class="space-y-6 px-5 py-6" novalidate>
                @csrf
                @method('PUT')

                <h3 class="text-center text-xl font-semibold text-gray-900">{{ $company->establishment_name }}</h3>

                <div class="max-w-xl">
                    <x-form-row :label="__('Employee Punch Buffer Time (In Minutes)')" for="punch_buffer_minutes" :messages="$errors->get('punch_buffer_minutes')">
                        <input type="number" min="0" id="punch_buffer_minutes" name="punch_buffer_minutes" class="{{ $input }}"
                               value="{{ old('punch_buffer_minutes', $setting->punch_buffer_minutes) }}">
                    </x-form-row>
                </div>

                @foreach (config('attendance.shift_types') as $type => $definition)
                    @php
                        $configured = collect(range(1, $definition['slots']))
                            ->filter(fn ($slot) => $shifts->has("{$type}.{$slot}") || filled(old("shifts.{$type}.{$slot}.name")))
                            ->max() ?? 1;
                    @endphp
                    <section class="rounded-lg border border-gray-200" x-data="{ shown: {{ $configured }}, max: {{ $definition['slots'] }} }">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <h4 class="text-base font-semibold text-gray-900">{{ __($definition['label']) }}</h4>
                            @if ($definition['slots'] > 1)
                                <button type="button" x-show="shown < max" x-on:click="shown++"
                                        class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">
                                    {{ __('Add :type', ['type' => __($definition['label'])]) }}
                                </button>
                            @endif
                        </div>

                        <div class="divide-y divide-gray-100">
                            @for ($slot = 1; $slot <= $definition['slots']; $slot++)
                                @php
                                    $shift = $shifts->get("{$type}.{$slot}");
                                    $prefix = "shifts.{$type}.{$slot}";
                                    $name = fn ($field) => "shifts[{$type}][{$slot}][{$field}]";
                                    $title = $definition['slots'] > 1 ? __($definition['label']).' '.$slot : __($definition['label']);
                                    $modal = "shift-settings-{$type}-{$slot}";
                                    $slotErrors = collect($errors->keys())->contains(fn ($k) => str_starts_with($k, $prefix.'.'));
                                @endphp
                                <div class="px-4 py-4" @if ($slot > 1) x-show="shown >= {{ $slot }}" @if ($slot > $configured) style="display: none;" @endif @endif>
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                        <h5 class="text-sm font-semibold text-gray-800">
                                            {{ $title }}
                                            @if ($shift?->employees_count)
                                                <span class="ms-2 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">{{ trans_choice(':count employee|:count employees', $shift->employees_count) }}</span>
                                            @endif
                                        </h5>
                                        <button type="button" x-on:click="$dispatch('open-modal', @js($modal))"
                                                class="rounded-md border px-3 py-1.5 text-sm font-medium {{ $slotErrors ? 'border-red-300 text-danger' : 'border-gray-300 text-gray-700' }} hover:bg-gray-50">
                                            {{ __('Shift Settings') }}
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="sm:col-span-2 lg:col-span-3 lg:max-w-md">
                                            <label for="{{ $modal }}-name" class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
                                            <input id="{{ $modal }}-name" name="{{ $name('name') }}" value="{{ old("{$prefix}.name", $shift?->name) }}" class="mt-1 {{ $input }}"
                                                   placeholder="{{ __('Leave empty if not used') }}">
                                            <x-input-error class="mt-1" :messages="$errors->get($prefix.'.name')" />
                                        </div>
                                        @foreach ($definition['parts'] as $code => $partLabel)
                                            @foreach (['start' => 'Start Time', 'end' => 'End Time'] as $edge => $edgeLabel)
                                                @php
                                                    $key = $prefix.'.timings.'.$code.'.'.$edge;
                                                @endphp
                                                <div>
                                                    <label for="{{ $modal }}-{{ $code }}-{{ $edge }}" class="block text-sm font-medium text-gray-700">
                                                        {{ $partLabel ? __($partLabel).' — ' : '' }}{{ __($edgeLabel) }}
                                                    </label>
                                                    <input type="time" id="{{ $modal }}-{{ $code }}-{{ $edge }}" name="shifts[{{ $type }}][{{ $slot }}][timings][{{ $code }}][{{ $edge }}]"
                                                           value="{{ old($key, $shift?->timings[$code][$edge] ?? '') }}" class="mt-1 {{ $input }}">
                                                    <x-input-error class="mt-1" :messages="$errors->get($key)" />
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>

                                    {{-- Pay rules for this shift. --}}
                                    <x-modal :name="$modal" :show="$slotErrors && collect($errors->keys())->contains(fn ($k) => str_starts_with($k, $prefix.'.') && ! str_contains($k, '.timings.') && ! str_ends_with($k, '.name'))" max-width="2xl">
                                        <div class="border-b border-gray-200 px-6 py-4">
                                            <h2 class="text-lg font-semibold text-gray-900">{{ __(':shift - Settings', ['shift' => $title]) }}</h2>
                                        </div>
                                        <div class="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-5">
                                            @foreach (config('attendance.rules') as $field => [$question, $kind, $sublabel])
                                                @php
                                                    $current = old("{$prefix}.{$field}", $shift ? $shift->{$field} : \App\Models\Shift::RULE_DEFAULTS[$field]);
                                                    $current = is_bool($current) ? (int) $current : $current;
                                                @endphp
                                                <div>
                                                    @if ($question)
                                                        <h6 class="mb-2 text-sm font-semibold text-gray-900">{{ __($question) }}</h6>
                                                    @endif
                                                    @if ($sublabel)
                                                        <label for="{{ $modal }}-{{ $field }}" class="mb-1 block text-xs text-gray-500">{{ __($sublabel) }}</label>
                                                    @endif
                                                    @if ($kind === 'number')
                                                        <input type="number" step="{{ $field === 'ot_after_hours' ? '0.01' : '1' }}" min="0" id="{{ $modal }}-{{ $field }}"
                                                               name="{{ $name($field) }}" value="{{ $current }}" class="{{ $input }}">
                                                    @else
                                                        <select id="{{ $modal }}-{{ $field }}" name="{{ $name($field) }}" class="{{ $select }}" @unless ($sublabel) aria-label="{{ __($question) }}" @endunless>
                                                            @foreach ($kind === 'yes_no' ? ['0' => __('No'), '1' => __('Yes')] : config("attendance.{$kind}") as $value => $label)
                                                                <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ __($label) }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                    <x-input-error class="mt-1" :messages="$errors->get($prefix.'.'.$field)" />
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                            <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                                        </div>
                                    </x-modal>
                                </div>
                            @endfor
                        </div>
                    </section>
                @endforeach

                <div class="flex justify-end">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>
            </form>
        @endif
    </div>
    </div>
</x-app-layout>
