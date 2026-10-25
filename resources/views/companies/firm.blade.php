@use('App\Http\Controllers\FirmController')
@use('App\Models\Firm')
<x-app-layout>
    <x-slot name="header">{{ __('Company') }}</x-slot>

    <x-section-tabs section="company" />

    @php
        $input = 'mt-1.5 control';
        $label = 'block text-[15px] font-bold text-gray-700';
        $val = fn (string $field) => old($field, $firm->{$field} instanceof \DateTimeInterface ? $firm->{$field}->format('Y-m-d') : $firm->{$field});
        $blankPerson = array_fill_keys(array_keys(Firm::PERSON_FIELDS), '') + ['is_primary' => false, 'is_factory_manager' => false];
        $owners = collect(old('owners', $firm->owners ?? []))->map(fn ($o) => array_merge($blankPerson, (array) $o))->values()->all() ?: [array_merge($blankPerson, ['is_primary' => true])];
        $manager = array_merge($blankPerson, (array) old('factory_manager', $firm->factory_manager ?? []));
        $step = in_array((int) request('step'), [1, 2, 3], true) ? (int) request('step') : 1;
        // Open the step with the first error.
        if ($errors->any()) {
            $step = collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'mail_')) ? 3 : (collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'owners') || str_starts_with($k, 'factory_manager')) ? 2 : 1);
        }
        $personField = function (string $prefix, string $field, string $text) use ($input, $label) {
            $type = match ($field) { 'dob', 'position_date' => 'date', 'email' => 'email', default => 'text' };
            $list = match ($field) { 'state' => 'firm-states', 'district' => 'firm-districts', 'taluka' => 'firm-talukas', default => null };

            return compact('type', 'list', 'text');
        };
    @endphp

    <form method="POST" action="{{ route('company.firm.update') }}" enctype="multipart/form-data"
          x-data="{ step: {{ $step }}, owners: @js($owners), manager: @js($manager),
                    addOwner() { this.owners.push(@js($blankPerson)) },
                    copyManager() { const o = this.owners.find((x) => x.is_factory_manager); if (o) { this.manager = { ...o } } } }">
        @csrf
        @method('PUT')
        <input type="hidden" name="step" :value="step">

        <div>
            <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
                <div>
                    <h1 class="text-2xl font-semibold text-ink">{{ __('Contractor firm') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('Your own firm: its details, owners and factory manager, and the email account the HRMS sends from.') }}</p>
                </div>
            </div>
            <div class="card">

            {{-- Steps --}}
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 px-5 py-3" aria-label="{{ __('Steps') }}">
                @foreach ([1 => __('Establishment Details'), 2 => __('Owners Details'), 3 => __('Mail setup')] as $number => $title)
                    <button type="button" x-on:click="step = {{ $number }}"
                            :class="step === {{ $number }} ? 'bg-ink text-canvas' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="rounded-md px-3 py-2 text-sm font-semibold">{{ $number }}. {{ $title }}</button>
                @endforeach
            </nav>

            <x-input-error class="px-5 pt-4" :messages="collect($errors->getMessages())->flatten()->unique()->all()" />

            {{-- 1. Establishment details --}}
            <section x-show="step === 1" class="space-y-6 px-5 py-5" @if ($step !== 1) style="display: none;" @endif>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Establishment Details:') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div><label class="{{ $label }}" for="establishment_name">{{ __('Establishment Name') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="establishment_name" name="establishment_name" required value="{{ $val('establishment_name') }}" class="{{ $input }}"></div>
                    @foreach (['sector' => [__('Sector (Ownership Type)'), Firm::SECTORS], 'organization_type' => [__('Organization Type'), Firm::ORGANIZATION_TYPES], 'establishment_category' => [__('Establishment Category'), Firm::CATEGORIES]] as $field => [$text, $options])
                        <div><label class="{{ $label }}" for="{{ $field }}">{{ $text }}</label>
                            <select id="{{ $field }}" name="{{ $field }}" class="{{ $input }}">
                                <option value="">{{ __('Select') }}</option>
                                @foreach ($options as $option)<option @selected($val($field) === $option)>{{ $option }}</option>@endforeach
                            </select></div>
                    @endforeach
                    @foreach (['registered_on' => [__('Company Registration Date'), 'date'], 'commenced_on' => [__('Date of commencement'), 'date'], 'registration_no' => [__('Registration No. - CIN No. / Deed No. / MSME'), 'text'], 'pan' => [__('PAN'), 'text']] as $field => [$text, $type])
                        <div><label class="{{ $label }}" for="{{ $field }}">{{ $text }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ $val($field) }}" class="{{ $input }}" @if ($field === 'pan') maxlength="10" style="text-transform: uppercase;" @endif></div>
                    @endforeach
                    <label class="flex items-center gap-2 pt-7 text-sm font-medium text-gray-700">
                        <input type="hidden" name="is_factory_registered" value="0">
                        <input type="checkbox" name="is_factory_registered" value="1" @checked(old('is_factory_registered', $firm->is_factory_registered)) class="rounded border-field text-primary focus:ring-primary/30">
                        {{ __('Is factory registered under the Factory Act') }}
                    </label>
                </div>

                <h4 class="font-semibold text-gray-900">{{ __('Address') }}</h4>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (['premise_no' => 'Premise No.', 'address_line_1' => 'Address Line 1', 'address_line_2' => 'Address Line 2', 'village' => 'Village', 'state' => 'State', 'district' => 'District', 'taluka' => 'Taluka', 'pin_code' => 'PIN Code', 'nearest_police_station' => 'Nearest Police Station'] as $field => $text)
                        <div><label class="{{ $label }}" for="{{ $field }}">{{ __($text) }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" value="{{ $val($field) }}" class="{{ $input }}" autocomplete="off"
                                   @if (in_array($field, ['state', 'district', 'taluka'], true)) list="firm-{{ $field === 'taluka' ? 'talukas' : $field.'s' }}" @endif
                                   @if ($field === 'pin_code') inputmode="numeric" maxlength="6" @endif></div>
                    @endforeach
                </div>

                <h4 class="font-semibold text-gray-900">{{ __('Logo, stamp and signature') }}</h4>
                <p class="text-sm text-gray-500">{{ __('The logo and the stamp and signature are printed on employee ID cards.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (FirmController::IMAGES as $field => [$column, $text])
                        <div>
                            <label class="{{ $label }}" for="{{ $field }}">{{ __($text) }}</label>
                            @if ($firm->{$column})
                                <img src="{{ route('company.firm.image', $field) }}" alt="{{ __($text) }}" class="mt-2 h-20 rounded border border-gray-200 bg-white object-contain p-1">
                            @endif
                            <input id="{{ $field }}" name="{{ $field }}" type="file" accept="image/*" class="mt-2 block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- 2. Owners and factory manager --}}
            <section x-show="step === 2" class="space-y-6 px-5 py-5" @if ($step !== 2) style="display: none;" @endif>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Owners Details:') }}</h3>
                    <button type="button" x-on:click="addOwner()" class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">+ {{ __('Add owner') }}</button>
                </div>
                <template x-for="(owner, i) in owners" :key="i">
                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <p class="font-semibold text-gray-900" x-text="`{{ __('Owner') }} ${i + 1}` + ((owner.first_name || owner.last_name) ? ` — ${owner.first_name} ${owner.last_name}` : '')"></p>
                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                <label class="flex items-center gap-2"><input type="hidden" :name="`owners[${i}][is_primary]`" :value="owner.is_primary ? 1 : 0">
                                    <input type="checkbox" x-model="owner.is_primary" class="rounded border-field text-primary focus:ring-primary/30"> {{ __('Is Primary') }}</label>
                                <label class="flex items-center gap-2"><input type="hidden" :name="`owners[${i}][is_factory_manager]`" :value="owner.is_factory_manager ? 1 : 0">
                                    <input type="checkbox" x-model="owner.is_factory_manager" class="rounded border-field text-primary focus:ring-primary/30"> {{ __('Is factory manager') }}</label>
                                <button type="button" x-on:click="owners.splice(i, 1)" x-show="owners.length > 1" class="text-sm font-medium text-danger hover:underline">{{ __('Remove') }}</button>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach (Firm::PERSON_FIELDS as $field => $text)
                                @php $f = $personField('owners', $field, $text); @endphp
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __($text) }}</label>
                                    @if ($field === 'gender')
                                        <select :name="`owners[${i}][gender]`" x-model="owner.gender" class="{{ $input }}">
                                            <option value="">{{ __('Select Gender') }}</option><option value="male">{{ __('Male') }}</option><option value="female">{{ __('Female') }}</option><option value="other">{{ __('Other') }}</option>
                                        </select>
                                    @else
                                        <input :name="`owners[${i}][{{ $field }}]`" x-model="owner.{{ $field }}" type="{{ $f['type'] }}" @if ($f['list']) list="{{ $f['list'] }}" @endif autocomplete="off" class="{{ $input }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </template>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-5">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Factory Manager Details:') }}</h3>
                    <button type="button" x-on:click="copyManager()" x-show="owners.some((o) => o.is_factory_manager)" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Copy from the owner marked factory manager') }}</button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (Firm::PERSON_FIELDS as $field => $text)
                        @php $f = $personField('factory_manager', $field, $text); @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="fm-{{ $field }}">{{ __($text) }}</label>
                            @if ($field === 'gender')
                                <select id="fm-gender" name="factory_manager[gender]" x-model="manager.gender" class="{{ $input }}">
                                    <option value="">{{ __('Select Gender') }}</option><option value="male">{{ __('Male') }}</option><option value="female">{{ __('Female') }}</option><option value="other">{{ __('Other') }}</option>
                                </select>
                            @else
                                <input id="fm-{{ $field }}" name="factory_manager[{{ $field }}]" x-model="manager.{{ $field }}" type="{{ $f['type'] }}" @if ($f['list']) list="{{ $f['list'] }}" @endif autocomplete="off" class="{{ $input }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- 3. Mail setup --}}
            <section x-show="step === 3" class="space-y-6 px-5 py-5" @if ($step !== 3) style="display: none;" @endif>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Mail setup') }}</h3>
                <p class="text-sm text-gray-600">{{ __('The email account the HRMS sends from (password reset and other mail). For Gmail use host smtp.gmail.com, port 465 and an app password (Google Account › Security › App passwords).') }}</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div><label class="{{ $label }}" for="mail_protocol">{{ __('Protocol') }}</label>
                        <input id="mail_protocol" name="mail_protocol" value="{{ $val('mail_protocol') ?? 'SMTP' }}" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}" for="mail_host">{{ __('SMTP host') }}</label>
                        <input id="mail_host" name="mail_host" value="{{ $val('mail_host') }}" placeholder="smtp.gmail.com" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}" for="mail_port">{{ __('SMTP port') }}</label>
                        <input id="mail_port" name="mail_port" type="number" value="{{ $val('mail_port') }}" placeholder="465" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}" for="mail_username">{{ __('SMTP user') }}</label>
                        <input id="mail_username" name="mail_username" value="{{ $val('mail_username') }}" placeholder="name@example.com" autocomplete="off" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}" for="mail_password">{{ __('SMTP password') }}</label>
                        <input id="mail_password" name="mail_password" type="password" autocomplete="new-password" placeholder="{{ $firm->mail_password ? __('Saved — leave blank to keep') : '' }}" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}" for="mail_type">{{ __('Mail type') }}</label>
                        <select id="mail_type" name="mail_type" class="{{ $input }}">
                            <option value="html" @selected($val('mail_type') !== 'text')>Html</option>
                            <option value="text" @selected($val('mail_type') === 'text')>Text</option>
                        </select></div>
                </div>
                @if ($firm->mailConfig())
                    <div class="flex flex-wrap items-center gap-3 rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        {{ __('Check the saved settings work:') }}
                        <button type="submit" form="test-mail" class="inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control border border-line-strong bg-surface px-3 text-13 font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Send test email to :email', ['email' => auth()->user()->email]) }}</button>
                    </div>
                @endif
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-5 py-4">
                <div class="flex gap-2">
                    <button type="button" x-show="step > 1" x-on:click="step--" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Previous') }}</button>
                    <button type="button" x-show="step < 3" x-on:click="step++" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Next') }}</button>
                </div>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </div>
        </div>
    </form>

    <form id="test-mail" method="POST" action="{{ route('company.firm.test-mail') }}" class="hidden">@csrf</form>

    <datalist id="firm-states">@foreach ($states as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="firm-districts">@foreach ($districts as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    <datalist id="firm-talukas">@foreach ($talukas as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
</x-app-layout>
