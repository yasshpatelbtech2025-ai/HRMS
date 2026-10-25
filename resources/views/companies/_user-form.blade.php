{{--
    Add / edit login form, shown inside a modal.
    Expects: $user (new or existing), $modal (modal name), $companies.
--}}
@php
    $editing = $user->exists;
    $self = $editing && $user->is(auth()->user());
    // Repopulate from old input only for the form that was submitted.
    $mine = old('_modal') === $modal;
    $value = fn (string $field, $default = null) => $mine ? old($field, $default) : ($user->{$field} ?? $default);
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $assigned = $mine ? array_map('intval', old('companies', [])) : $user->companies->modelKeys();
@endphp

<form method="POST" action="{{ $editing ? route('company.user-access.update', $user) : route('company.user-access.store') }}"
      x-data="{ role: @js($value('role', 'user')) }">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-line px-6 py-4">
        <h2 class="text-lg font-semibold text-ink">{{ $editing ? __('Edit login') : __('Add login') }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        <x-form-row :label="__('Name')" for="{{ $modal }}-name" required :messages="$error('name')">
            <x-text-input id="{{ $modal }}-name" name="name" class="block w-full" :value="$value('name')" required autocomplete="off" />
        </x-form-row>

        <x-form-row :label="__('Email')" for="{{ $modal }}-email" required :messages="$error('email')">
            <x-text-input id="{{ $modal }}-email" name="email" type="email" class="block w-full" :value="$value('email')" required autocomplete="off" />
        </x-form-row>

        <x-form-row :label="$editing ? __('New password') : __('Password')" for="{{ $modal }}-password" :required="! $editing" :messages="$error('password')">
            <x-text-input id="{{ $modal }}-password" name="password" type="password" class="block w-full" :required="! $editing" autocomplete="new-password" />
            @if ($editing)<p class="mt-1 text-xs text-gray-500">{{ __('Leave blank to keep the current password.') }}</p>@endif
        </x-form-row>

        <x-form-row :label="__('Confirm password')" for="{{ $modal }}-password_confirmation" :required="! $editing">
            <x-text-input id="{{ $modal }}-password_confirmation" name="password_confirmation" type="password" class="block w-full" :required="! $editing" autocomplete="new-password" />
        </x-form-row>

        <x-form-row :label="__('Role')" required :messages="$error('role')">
            <div class="flex flex-wrap gap-4 pt-2">
                @foreach (\App\Models\User::ROLES as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="role" value="{{ $key }}" x-model="role" @disabled($self && $key !== 'admin')
                               class="border-gray-300 text-brand-600 focus:ring-brand-500">
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-500" x-show="role === 'admin'">{{ __('Admins see every company, the admin-only sections (such as Invoice) and can delete.') }}</p>
            <p class="mt-1 text-xs text-gray-500" x-show="role === 'user'">{{ __('Users see only the companies ticked below and cannot delete.') }}</p>
        </x-form-row>

        <x-form-row :label="__('Status')">
            <label class="flex items-center gap-2 pt-2 text-sm text-gray-700">
                <input type="hidden" name="is_active" value="{{ $self ? 1 : 0 }}">
                <input type="checkbox" name="is_active" value="1" @checked($value('is_active', true)) @disabled($self)
                       class="rounded border-field text-primary focus:ring-primary/30">
                {{ __('Active (can sign in)') }}
            </label>
        </x-form-row>

        <x-form-row :label="__('Companies')" x-show="role === 'user'" required :messages="array_merge($error('companies'), $error('companies.*'))">
            <div class="max-h-60 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2">
                @forelse ($companies as $company)
                    <label class="flex items-center gap-2 rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-50">
                        <input type="checkbox" name="companies[]" value="{{ $company->id }}" @checked(in_array($company->id, $assigned, true))
                               class="rounded border-field text-primary focus:ring-primary/30">
                        {{ $company->establishment_name }}
                    </label>
                @empty
                    <p class="px-2 py-1 text-sm text-gray-500">{{ __('Add a company first.') }}</p>
                @endforelse
            </div>
        </x-form-row>
    </div>

    <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
