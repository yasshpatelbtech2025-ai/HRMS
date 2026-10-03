<x-app-layout>
    <x-slot name="header">{{ __('Compliance') }}</x-slot>

    @include('compliance.forms._styles')

    @php
        $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
        $btn = 'rounded-md px-3 py-2 text-sm font-semibold';
        $query = request()->except('output');
        $examView = in_array($view, config('compliance.examination_views'), true);
        $noFilter = $examView || in_array($view, ['form-13', 'form-15', 'id-card-register'], true);
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('compliance.index', ['company' => $company->uuid, 'act' => $act]) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Compliance') }}</a>
            <h2 class="text-2xl font-bold text-gray-900">{{ $title }}</h2>
            <p class="text-sm text-gray-500">{{ $company->label }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('compliance.forms.show', [$company, $form]) }}" class="flex flex-wrap items-center gap-2">
                @if (in_array($view, ['form-a', 'form-b', 'form-c', 'form-d', 'form-16', 'form-17', 'form-18', 'form-20', 'form-21', 'form-22', 'form-23'], true))
                    <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="{{ $select }}" aria-label="{{ __('Month') }}">
                @elseif (in_array($view, ['form-14', 'gratuity-form-f'], true))
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
                @elseif ($view === 'form-29')
                    <select name="year" class="{{ $select }}" aria-label="{{ __('Year') }}">
                        @foreach (range((int) date('Y'), 2020) as $option)<option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>@endforeach
                    </select>
                @elseif ($view === 'bonus-form-d')
                    <select name="year" class="{{ $select }}" aria-label="{{ __('Accounting year') }}">
                        @foreach (range((int) date('Y'), 2020) as $option)<option value="{{ $option }}" @selected($startYear === $option)>{{ $option }} - {{ $option + 1 }}</option>@endforeach
                    </select>
                @endif
                @unless ($noFilter)
                    <button class="{{ $btn }} bg-emerald-600 text-white hover:bg-emerald-700">{{ __('Find') }}</button>
                @endunless
            </form>
            <a href="{{ route('compliance.forms.show', [$company, $form] + $query + ['output' => 'print']) }}" target="_blank" class="{{ $btn }} bg-indigo-600 text-white hover:bg-indigo-700">{{ __('Print') }}</a>
            <a href="{{ route('compliance.forms.show', [$company, $form] + $query + ['output' => 'pdf']) }}" class="{{ $btn }} bg-gray-800 text-white hover:bg-gray-900">{{ __('Download PDF') }}</a>
        </div>
    </div>

    @unless (old('_modal'))
        <x-input-error class="mb-3" :messages="collect($errors->getMessages())->flatten()->unique()->all()" />
    @endunless

    @php $wide = in_array($view, ['form-a', 'form-b', 'form-c', 'form-d', 'form-13', 'form-15', 'form-16', 'form-17', 'form-18', 'form-20', 'form-21', 'form-22', 'form-23', 'form-29', 'form-32', 'id-card-register'], true); @endphp
    @if ($examView)
        @include('compliance.forms._examinations')
    @else
        <div class="overflow-x-auto rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="sheet {{ $wide ? '' : 'mx-auto max-w-5xl' }}">
                @include('compliance.forms.sheets.'.$view)
            </div>
        </div>
    @endif
</x-app-layout>
