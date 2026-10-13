{{-- Shared by create and edit. Settings-style: section description on the left, fields on the right. --}}
<form method="POST" action="{{ $action }}" data-guard class="space-y-5 pb-20">
    @csrf
    @if ($company->exists) @method('PUT') @endif

    <x-ui.card>
        <div class="grid gap-x-8 gap-y-5 lg:grid-cols-[16rem_1fr]">
            <div>
                <h2 class="text-sm font-semibold text-ink">{{ __('Establishment') }}</h2>
                <p class="mt-1 text-13 text-ink-3">{{ __('The name as it appears on registrations and challans.') }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('Establishment name')" for="establishment_name" required class="sm:col-span-2">
                    <x-ui.input name="establishment_name" :value="$company->establishment_name" required autofocus maxlength="255" />
                </x-ui.field>
                <x-ui.field :label="__('Connected company')" for="parent_company_id" :hint="__('If this is a unit or branch of another client.')">
                    <x-ui.combobox name="parent_company_id" :value="$company->parent_company_id" :placeholder="__('None')" :empty-label="__('None')"
                                   :options="$parents->map(fn ($p) => ['value' => $p->id, 'label' => $p->establishment_name])->all()" />
                </x-ui.field>
                <x-ui.field :label="__('Organisation size')" for="organization_size" :hint="__('Total workers at the site.')">
                    <x-ui.input name="organization_size" type="number" min="0" inputmode="numeric" :value="$company->organization_size" />
                </x-ui.field>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="grid gap-x-8 gap-y-5 lg:grid-cols-[16rem_1fr]">
            <div>
                <h2 class="text-sm font-semibold text-ink">{{ __('Address') }}</h2>
                <p class="mt-1 text-13 text-ink-3">{{ __('Used on letters, registers and compliance forms.') }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('Address line 1')" for="address_line_1" class="sm:col-span-2">
                    <x-ui.input name="address_line_1" :value="$company->address_line_1" autocomplete="address-line1" />
                </x-ui.field>
                <x-ui.field :label="__('Address line 2')" for="address_line_2" class="sm:col-span-2">
                    <x-ui.input name="address_line_2" :value="$company->address_line_2" autocomplete="address-line2" />
                </x-ui.field>
                <x-ui.field :label="__('Village')" for="village"><x-ui.input name="village" :value="$company->village" /></x-ui.field>
                <x-ui.field :label="__('Taluka')" for="taluka"><x-ui.input name="taluka" :value="$company->taluka" /></x-ui.field>
                <x-ui.field :label="__('District')" for="district"><x-ui.input name="district" :value="$company->district" /></x-ui.field>
                <x-ui.field :label="__('State')" for="state"><x-ui.input name="state" :value="$company->state" autocomplete="address-level1" /></x-ui.field>
            </div>
        </div>
    </x-ui.card>

    <x-ui.form-actions :cancel="route('company.index')" :submit="$submit" />
</form>
