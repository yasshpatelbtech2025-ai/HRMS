<x-app-layout>
    <x-slot name="header">{{ __('Edit company') }}</x-slot>

    <div class="max-w-3xl space-y-6">
        <form method="POST" action="{{ route('company.update', $company) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            @csrf
            @method('PUT')
            @include('companies._form', ['submit' => __('Save changes')])
        </form>

        <section id="departments" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="flex items-baseline justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-900">{{ __('Departments') }}</h3>
                <a href="{{ route('employees.index', ['company_id' => $company->id]) }}" class="text-sm font-medium text-brand-700 hover:text-brand-600">
                    {{ trans_choice(':count employee|:count employees', $company->employees_count) }}
                </a>
            </div>

            @if ($company->departments->isEmpty())
                <p class="mt-3 text-sm text-gray-500">{{ __('No departments yet. Add one so employees can be assigned to it.') }}</p>
            @else
                <ul class="mt-4 divide-y divide-gray-100 rounded-lg ring-1 ring-gray-200">
                    @foreach ($company->departments as $department)
                        <li class="flex items-center justify-between px-4 py-2.5 text-sm">
                            <span class="text-gray-900">{{ $department->name }}</span>
                            <form method="POST" action="{{ route('company.departments.destroy', [$company, $department]) }}" onsubmit="return confirm(@js(__('Remove the :name department?', ['name' => $department->name])))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">{{ __('Remove') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('company.departments.store', $company) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                @csrf
                <div class="flex-1">
                    <x-input-label for="department_name" :value="__('New department')" class="sr-only" />
                    <x-text-input id="department_name" name="name" class="block w-full" :value="old('name')" :placeholder="__('Department name, e.g. Finance')" required />
                    <x-input-error class="mt-2" :messages="$errors->department->get('name')" />
                </div>
                <x-primary-button>{{ __('Add department') }}</x-primary-button>
            </form>
        </section>
    </div>
</x-app-layout>
