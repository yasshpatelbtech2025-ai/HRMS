<x-app-layout>
    <x-slot name="header">{{ __('Configs') }}</x-slot>

    <x-section-tabs section="configs" />

    @php
        $select = 'control';
        $input = 'control';
    @endphp

    <div>
        <x-ui.page-header :title="__('Attendance setting')" :description="__('Shift times and the rules used to turn punches into present, half day or absent.')">
            <x-slot:actions>
                <form method="GET" action="{{ route('configs.index') }}" class="flex items-center gap-2">
                    <label for="settings-company" class="text-13 font-medium text-ink-2">{{ __('Company') }}</label>
                    <select id="settings-company" name="company" onchange="this.form.submit()" class="control-inline min-w-56">
                        @foreach ($companies as $option)
                            <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                        @endforeach
                    </select>
                </form>
            </x-slot:actions>
        </x-ui.page-header>
        <div class="card">

        @if (! $company)
            <x-empty-state icon="building" :title="__('No company yet')" class="py-12">
                {{ __('Add a company first.') }}
                <x-slot name="action"><x-ui.button variant="secondary" :href="route('company.index')">{{ __('Go to Company') }}</x-ui.button></x-slot>
            </x-empty-state>
        @else
            <form method="POST" action="{{ route('configs.attendance.update', $company) }}" class="space-y-6 px-5 py-6 sm:px-6" novalidate data-unsaved-warning>
                @csrf
                @method('PUT')

                <p class="text-sm text-ink-2">{{ __('Settings for') }} <span class="font-semibold text-ink">{{ $company->establishment_name }}</span></p>

                <div class="max-w-xl">
                    <x-form-row :label="__('Employee Punch Buffer Time (In Minutes)')" for="punch_buffer_minutes" :messages="$errors->get('punch_buffer_minutes')"
                                :help="__('Punches this many minutes apart count as one, so a double tap on the machine is not two punches.')">
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
                    <section class="rounded-card border border-line" x-data="{ shown: {{ $configured }}, max: {{ $definition['slots'] }} }">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-sunken/50 px-4 py-3">
                            <h2 class="text-[15px] font-semibold text-ink">{{ __($definition['label']) }}</h2>
                            @if ($definition['slots'] > 1)
                                <button type="button" x-show="shown < max" x-on:click="shown++"
                                        class="inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control border border-line-strong bg-surface px-3 text-13 font-medium text-ink hover:bg-sunken">
                                    <x-icon name="plus" class="size-4" />{{ __('Add :type', ['type' => __($definition['label'])]) }}
                                </button>
                            @endif
                        </div>

                        <div class="divide-y divide-line">
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
                                        <h3 class="text-sm font-semibold text-ink">
                                            {{ $title }}
                                            @if ($shift?->employees_count)
                                                <x-ui.badge tone="primary" class="ms-2">{{ trans_choice(':count employee|:count employees', $shift->employees_count) }}</x-ui.badge>
                                            @endif
                                        </h3>
                                        <button type="button" x-on:click="$dispatch('open-modal', @js($modal))"
                                                class="inline-flex h-control-sm items-center gap-1.5 rounded-control border px-3 text-13 font-medium {{ $slotErrors ? 'border-danger/50 text-danger' : 'border-line-strong text-ink' }} hover:bg-sunken"><x-icon name="cog" class="size-4" />
                                            {{ __('Shift Settings') }}
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="sm:col-span-2 lg:col-span-3 lg:max-w-md">
                                            <label for="{{ $modal }}-name" class="control-label">{{ __('Title') }}</label>
                                            <input id="{{ $modal }}-name" name="{{ $name('name') }}" value="{{ old("{$prefix}.name", $shift?->name) }}" class="{{ $input }} px-3"
                                                   placeholder="{{ __('Leave empty if not used') }}">
                                            <x-input-error class="mt-1" :messages="$errors->get($prefix.'.name')" />
                                        </div>
                                        @foreach ($definition['parts'] as $code => $partLabel)
                                            @foreach (['start' => 'Start Time', 'end' => 'End Time'] as $edge => $edgeLabel)
                                                @php
                                                    $key = $prefix.'.timings.'.$code.'.'.$edge;
                                                @endphp
                                                <div>
                                                    <label for="{{ $modal }}-{{ $code }}-{{ $edge }}" class="control-label">
                                                        {{ $partLabel ? __($partLabel).' — ' : '' }}{{ __($edgeLabel) }}
                                                    </label>
                                                    <input type="time" id="{{ $modal }}-{{ $code }}-{{ $edge }}" name="shifts[{{ $type }}][{{ $slot }}][timings][{{ $code }}][{{ $edge }}]"
                                                           value="{{ old($key, $shift?->timings[$code][$edge] ?? '') }}" class="{{ $input }} px-3">
                                                    <x-input-error class="mt-1" :messages="$errors->get($key)" />
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>

                                    {{-- Pay rules for this shift. --}}
                                    <x-modal :name="$modal" :show="$slotErrors && collect($errors->keys())->contains(fn ($k) => str_starts_with($k, $prefix.'.') && ! str_contains($k, '.timings.') && ! str_ends_with($k, '.name'))" max-width="2xl">
                                        <div class="border-b border-line px-6 py-4">
                                            <h2 class="text-lg font-semibold text-ink">{{ __(':shift - Settings', ['shift' => $title]) }}</h2>
                                        </div>
                                        <div class="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-5">
                                            @foreach (config('attendance.rules') as $field => [$question, $kind, $sublabel])
                                                @php
                                                    $current = old("{$prefix}.{$field}", $shift ? $shift->{$field} : \App\Models\Shift::RULE_DEFAULTS[$field]);
                                                    $current = is_bool($current) ? (int) $current : $current;
                                                @endphp
                                                <div>
                                                    @if ($question)
                                                        <h3 class="mb-2 text-sm font-semibold text-ink">{{ __($question) }}</h3>
                                                    @endif
                                                    @if ($sublabel)
                                                        <label for="{{ $modal }}-{{ $field }}" class="mb-1.5 block text-13 text-ink-2">{{ __($sublabel) }}</label>
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
                                        <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                                            <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                                        </div>
                                    </x-modal>
                                </div>
                            @endfor
                        </div>
                    </section>
                @endforeach

                <x-ui.save-bar class="!-mx-5 !-mb-6 rounded-b-card sm:!-mx-6">
                    <x-slot:status>{{ __('Shift times and rules are saved together for this company.') }}</x-slot:status>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </x-ui.save-bar>
            </form>
        @endif
    </div>
    </div>
</x-app-layout>
