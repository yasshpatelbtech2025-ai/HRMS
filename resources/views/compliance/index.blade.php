<x-app-layout>
    <x-slot name="header">{{ __('Compliance') }}</x-slot>

    <x-section-tabs section="compliance" />

    @php $forms = $acts[$act]['forms']; @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Company compliance') }}</h2>
            <form method="GET" action="{{ route('compliance.index') }}">
                <input type="hidden" name="act" value="{{ $act }}">
                <select name="company" onchange="this.form.submit()" class="min-w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" aria-label="{{ __('Company') }}">
                    @foreach ($companies as $option)
                        <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Add a company first.') }} <a href="{{ route('company.index') }}" class="text-indigo-600 hover:underline">{{ __('Go to Company') }}</a></p>
        @else
            <div class="px-5 py-5">
                <h3 class="mb-4 text-center text-xl font-semibold text-gray-900">{{ $company->establishment_name }}</h3>

                <nav class="flex flex-wrap justify-center gap-2" aria-label="{{ __('Acts') }}">
                    @foreach ($acts as $key => $definition)
                        <a href="{{ route('compliance.index', ['company' => $company->uuid, 'act' => $key]) }}" @if ($key === $act) aria-current="page" @endif
                           class="relative rounded-md px-3 py-2 text-sm font-semibold {{ $key === $act ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $definition['label'] }}
                            @if ($definition['forms'])
                                <span class="ms-1 rounded-full {{ $key === $act ? 'bg-white/20' : 'bg-white' }} px-1.5 text-xs">{{ count($definition['forms']) }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="border-t border-gray-200">
                <h4 class="bg-gray-50 px-5 py-3 text-center text-sm font-bold uppercase text-gray-800">{{ $acts[$act]['title'] }}</h4>
                @if (empty($forms))
                    <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('The forms for this act will be added once their pages are shared.') }}</p>
                @else
                    <ul class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-3">
                        @foreach ($forms as $key => $definition)
                            <li class="border-gray-100 p-4 sm:border-b">
                                @if (isset($definition['view']))
                                    <a href="{{ route('compliance.forms.show', [$company, $key]) }}" class="font-medium text-indigo-700 hover:underline">{{ $definition['title'] }}</a>
                                @elseif (isset($definition['link']))
                                    <a href="{{ route($definition['link']) }}" class="font-medium text-indigo-700 hover:underline">{{ $definition['title'] }}</a>
                                    <span class="block text-xs text-gray-500">{{ __('The payslips in Payroll › Manage Employee Salary') }}</span>
                                @else
                                    <span class="font-medium text-gray-500">{{ $definition['title'] }}</span>
                                    <span class="ms-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ __('coming soon') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
