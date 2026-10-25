<x-app-layout>
    <x-slot name="header">{{ __('Compliance') }}</x-slot>

    <x-section-tabs section="compliance" />

    @php $forms = $acts[$act]['forms']; @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Company compliance') }}</h1>
            <form method="GET" action="{{ route('compliance.index') }}">
                <input type="hidden" name="act" value="{{ $act }}">
                <label class="flex min-w-0 max-w-full items-center gap-2"><span class="shrink-0 text-13 font-medium text-ink-2">{{ __('Company') }}</span><select name="company" onchange="this.form.submit()" class="min-w-64 control-inline">
                    @foreach ($companies as $option)
                        <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                    @endforeach
                </select></label>
            </form>
        </div>
        @php
            $live = collect($acts)->reject(fn ($a) => $a['coming_soon'] ?? false);
            $formCount = $live->sum(fn ($a) => count($a['forms'] ?? []));
        @endphp
        <x-stat-strip :items="[
            ['label' => __('Acts available'), 'value' => $live->count()],
            ['label' => __('Forms and registers'), 'value' => $formCount],
            ['label' => __('Coming soon'), 'value' => count($acts) - $live->count()],
        ]" />

        <div class="card">

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Add a company first.') }} <a href="{{ route('company.index') }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Go to Company') }}</a></p>
        @else
            <div class="px-5 py-5">
                <h3 class="mb-4 text-center text-xl font-semibold text-gray-900">{{ $company->establishment_name }}</h3>

                <nav class="flex flex-wrap justify-center gap-2" aria-label="{{ __('Acts') }}">
                    @foreach ($acts as $key => $definition)
                        <a href="{{ route('compliance.index', ['company' => $company->uuid, 'act' => $key]) }}" @if ($key === $act) aria-current="page" @endif
                           class="relative rounded-md px-3 py-2 text-sm font-semibold {{ $key === $act ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $definition['label'] }}
                            @if ($definition['coming_soon'] ?? false)
                                <span class="ms-1 rounded-full {{ $key === $act ? 'bg-white/20' : 'bg-white' }} px-1.5 text-xs font-medium">{{ __('coming soon') }}</span>
                            @elseif ($definition['forms'])
                                <span class="ms-1 rounded-full {{ $key === $act ? 'bg-white/20' : 'bg-white' }} px-1.5 text-xs">{{ count($definition['forms']) }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="border-t border-gray-200">
                <h4 class="bg-gray-50 px-5 py-3 text-center text-sm font-bold uppercase text-gray-800">{{ $acts[$act]['title'] }}</h4>
                @if ($acts[$act]['coming_soon'] ?? false)
                    <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __(':act forms are coming soon.', ['act' => $acts[$act]['label']]) }}</p>
                @elseif (empty($forms))
                    <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('The forms for this act will be added once their pages are shared.') }}</p>
                @else
                    <ul class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-3">
                        @foreach ($forms as $key => $definition)
                            <li class="border-gray-100 p-4 sm:border-b">
                                @if (isset($definition['view']))
                                    <a href="{{ route('compliance.forms.show', [$company, $key]) }}" class="font-medium text-brand-700 hover:underline">{{ $definition['title'] }}</a>
                                @elseif (isset($definition['link']))
                                    <a href="{{ route($definition['link']) }}" class="font-medium text-brand-700 hover:underline">{{ $definition['title'] }}</a>
                                    @isset ($definition['note'])<span class="block text-xs text-gray-500">{{ __($definition['note']) }}</span>@endisset
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
    </div>
</x-app-layout>
