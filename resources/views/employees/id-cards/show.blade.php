<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    @php($e = $employee)

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('employees.id-cards') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to id card list') }}</a>
        <div class="flex items-center gap-2">
            @if ($e->id_card_assigned_on)
                <span class="text-sm text-gray-600">{{ __('Assigned on :date', ['date' => $e->id_card_assigned_on->format('d M Y')]) }}</span>
            @else
                <form method="POST" action="{{ route('employees.id-cards.assign', $e) }}">
                    @csrf
                    <button class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Mark as assigned today') }}</button>
                </form>
            @endif
            <button type="button" onclick="window.print()" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Print Id Card') }}</button>
        </div>
    </div>

    {{-- Standard CR80 card: 54 × 85.6 mm, portrait. --}}
    <div class="flex justify-center">
        <div class="flex flex-col overflow-hidden rounded-xl bg-white text-center shadow ring-1 ring-gray-300 print:rounded-none print:shadow-none"
             style="width: 54mm; height: 85.6mm;">
            <div class="bg-gray-900 px-2 py-2 text-[9pt] font-bold uppercase leading-tight text-white">{{ $e->company?->establishment_name }}</div>
            <div class="flex flex-1 flex-col items-center px-2 pt-2">
                <x-employee-photo :employee="$e" class="h-[24mm] w-[20mm] !rounded-md text-base" />
                <p class="mt-1.5 text-[10pt] font-bold leading-tight text-gray-900">{{ $e->full_name }}</p>
                <p class="text-[8pt] text-gray-600">{{ $e->position?->name }}</p>
                <dl class="mt-1.5 w-full space-y-0.5 text-left text-[7.5pt] text-gray-800">
                    <div class="flex"><dt class="w-[17mm] text-gray-500">{{ __('Emp. ID') }}</dt><dd class="font-mono font-semibold">{{ $e->employee_code }}</dd></div>
                    <div class="flex"><dt class="w-[17mm] text-gray-500">{{ __('Dept.') }}</dt><dd>{{ $e->subDepartment?->name ?? $e->department?->name }}</dd></div>
                    <div class="flex"><dt class="w-[17mm] text-gray-500">{{ __('Blood Gr.') }}</dt><dd>{{ $e->blood_group ?? '—' }}</dd></div>
                    <div class="flex"><dt class="w-[17mm] text-gray-500">{{ __('Contact') }}</dt><dd>{{ $e->phone }}</dd></div>
                    <div class="flex"><dt class="w-[17mm] text-gray-500">{{ __('Joined') }}</dt><dd>{{ $e->date_of_joining?->format('d-m-Y') }}</dd></div>
                </dl>
            </div>
            <div class="border-t border-gray-200 px-2 py-1 text-[6.5pt] text-gray-500">{{ __('If found, please return to the company.') }}</div>
        </div>
    </div>
</x-app-layout>
