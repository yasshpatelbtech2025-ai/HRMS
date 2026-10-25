<x-app-layout>
    <x-slot name="header">{{ __('Compliance') }}</x-slot>

    @include('compliance.forms._styles')

    @php
        $select = 'control-inline';
        $btn = 'rounded-md px-3 py-2 text-sm font-semibold';
        $query = request()->except('output');
        $examView = in_array($view, config('compliance.examination_views'), true);
        $noFilter = $examView || in_array($view, ['form-13', 'form-15', 'id-card-register'], true);
    @endphp

    <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
        <div class="min-w-0">
            <a href="{{ route('compliance.index', ['company' => $company->uuid, 'act' => $act]) }}" class="mb-1.5 inline-flex items-center gap-1 text-13 text-ink-3 hover:text-ink hover:underline"><x-icon name="chevron-left" class="size-3" />{{ __('Compliance') }}</a>
            <h1 class="text-2xl font-semibold text-ink">{{ $title }}</h1>
            <p class="mt-1 text-sm text-ink-2">{{ $company->label }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('compliance.forms.show', [$company, $form]) }}" class="flex flex-wrap items-center gap-2">
                @if (in_array($view, ['form-a', 'form-b', 'form-c', 'form-d', 'form-16', 'form-17', 'form-18', 'form-20', 'form-21', 'form-22', 'form-23', 'pt-form-5', 'pf-statement'], true))
                    <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="{{ $select }}" aria-label="{{ __('Month') }}">
                @elseif (in_array($view, ['form-14', 'gratuity-form-f', 'pf-form-10c', 'pf-form-19', 'police-verification'], true))
                    <select name="employee" class="{{ $select }} min-w-64" aria-label="{{ __('Employee') }}">
                        <option value="">{{ __('-- Select employee --') }}</option>
                        @foreach ($employees as $option)
                            <option value="{{ $option->uuid }}" @selected($employee?->is($option))>{{ $option->employee_code }} - {{ $option->full_name }}</option>
                        @endforeach
                    </select>
                @elseif ($view === 'form-25')
                    <select name="month" class="{{ $select }}" aria-label="{{ __('Half year ending') }}">
                        <option value="june" @selected($half === 'june')>{{ __('June') }}</option>
                        <option value="december" @selected($half === 'december')>{{ __('December') }}</option>
                    </select>
                    <select name="year" class="{{ $select }}" aria-label="{{ __('Year') }}">
                        @foreach (range((int) date('Y'), 2020) as $option)<option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>@endforeach
                    </select>
                @elseif ($view === 'er-1')
                    <select name="quarter" class="{{ $select }}" aria-label="{{ __('Quarter') }}">
                        @foreach ([1 => 'Jan - Mar', 2 => 'Apr - Jun', 3 => 'Jul - Sep', 4 => 'Oct - Dec'] as $value => $label)<option value="{{ $value }}" @selected($quarter === $value)>{{ $label }}</option>@endforeach
                    </select>
                    <select name="year" class="{{ $select }}" aria-label="{{ __('Year') }}">
                        @foreach (range((int) date('Y'), 2020) as $option)<option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>@endforeach
                    </select>
                @elseif ($view === 'form-29' || str_starts_with($view, 'maternity-'))
                    <select name="year" class="{{ $select }}" aria-label="{{ __('Year') }}">
                        @foreach (range((int) date('Y'), 2020) as $option)<option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>@endforeach
                    </select>
                @elseif (in_array($view, ['bonus-form-a', 'bonus-form-b', 'bonus-form-c', 'bonus-form-d'], true))
                    <select name="year" class="{{ $select }}" aria-label="{{ __('Accounting year') }}">
                        @foreach (range((int) date('Y'), 2020) as $option)<option value="{{ $option }}" @selected($startYear === $option)>{{ $option }} - {{ $option + 1 }}</option>@endforeach
                    </select>
                @endif
                @unless ($noFilter)
                    <button class="{{ $btn }} bg-brand-600 text-white hover:bg-brand-700">{{ __('Find') }}</button>
                @endunless
            </form>
            @isset ($export)
                <a href="{{ route('compliance.forms.show', [$company, $form] + $query + ['output' => 'xlsx']) }}" class="{{ $btn }} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">{{ __('Excel') }}</a>
                <a href="{{ route('compliance.forms.show', [$company, $form] + $query + ['output' => 'csv']) }}" class="{{ $btn }} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">{{ __('CSV') }}</a>
            @endisset
            <a href="{{ route('compliance.forms.show', [$company, $form] + $query + ['output' => 'print']) }}" target="_blank" class="{{ $btn }} bg-brand-600 text-white hover:bg-brand-700">{{ __('Print') }}</a>
            @unless ($noPdf)
                <a href="{{ route('compliance.forms.show', [$company, $form] + $query + ['output' => 'pdf']) }}" class="{{ $btn }} border border-line-strong bg-surface text-ink shadow-hairline hover:bg-sunken">{{ __('Download PDF') }}</a>
            @endunless
        </div>
    </div>

    @unless (old('_modal'))
        <x-input-error class="mb-3" :messages="collect($errors->getMessages())->flatten()->unique()->all()" />
    @endunless

    @php $wide = in_array($view, ['form-a', 'form-b', 'form-c', 'form-d', 'form-13', 'form-15', 'form-16', 'form-17', 'form-18', 'form-20', 'form-21', 'form-22', 'form-23', 'form-29', 'form-32', 'id-card-register', 'bonus-form-a', 'bonus-form-c'], true); @endphp
    @if ($examView)
        @include('compliance.forms._examinations')
    @else
        <div class="overflow-x-auto card p-6">
            @if ($blank ?? false)
                <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    {{ __(':company has no employees yet, so this is the blank form to print and fill by hand.', ['company' => $company->establishment_name]) }}
                    <a href="{{ route('employees.create') }}" class="font-semibold underline">{{ __('Add an employee') }}</a>
                    {{ __('to have it filled in from their profile.') }}
                </div>
            @endif
            <div class="sheet {{ $wide ? '' : 'mx-auto max-w-5xl' }}">
                @include('compliance.forms.sheets.'.$view)
            </div>
        </div>
    @endif
</x-app-layout>
