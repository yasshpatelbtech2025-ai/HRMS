{{--
    Medical examination forms (5, 27-A, 30, 32): employees and their
    examinations on the left, the chosen certificate on the right, with
    add / edit / delete and the signed copy.
--}}
@php
    $link = fn ($e, $x = null) => route('compliance.forms.show', array_filter([$company, $form, 'employee' => $e->uuid, 'exam' => $x?->uuid]));
@endphp
<div class="flex flex-col gap-4 lg:flex-row lg:items-start">
    <aside class="shrink-0 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 lg:sticky lg:top-4 lg:w-80">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="text-[15px] font-bold text-gray-900">{{ __('Employees') }}</h3>
            <button type="button" x-data x-on:click="$dispatch('open-modal', 'examination-add')"
                    class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">+ {{ __('Add examination') }}</button>
        </div>
        <ul class="max-h-[70vh] divide-y divide-gray-100 overflow-y-auto" x-data="{ open: @js($employee?->uuid) }">
            @forelse ($employees as $e)
                @php $current = $employee?->is($e); @endphp
                <li>
                    <button type="button" x-on:click="open = open === @js($e->uuid) ? null : @js($e->uuid)"
                            class="flex w-full items-center justify-between gap-2 px-4 py-3 text-left text-sm font-semibold {{ $current ? 'bg-brand-50 text-brand-900' : 'bg-gray-50 text-gray-800 hover:bg-gray-100' }}">
                        <span>{{ $e->employee_code }} - {{ $e->full_name }}</span>
                        <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-gray-200">{{ $e->examinations->count() }}</span>
                    </button>
                    <ul x-show="open === @js($e->uuid)" @unless ($current) style="display: none;" @endunless>
                        @forelse ($e->examinations as $x)
                            <li>
                                <a href="{{ $link($e, $x) }}" class="block px-6 py-2 text-sm {{ $examination?->is($x) ? 'bg-brand-600 font-semibold text-white' : 'text-gray-700 hover:bg-gray-50' }}">
                                    {{ __('Examination') }} : {{ $x->examined_on->format('d-m-Y') }}
                                    @if ($x->result)<span class="ms-1 text-xs {{ $examination?->is($x) ? 'text-brand-100' : ($x->result === 'fit' ? 'text-emerald-600' : 'text-red-600') }}">({{ ucfirst($x->result) }})</span>@endif
                                </a>
                            </li>
                        @empty
                            <li class="px-6 py-2 text-sm text-gray-500">
                                {{ __('No examinations yet.') }}
                                <a href="{{ $link($e) }}" class="text-brand-600 hover:underline">{{ __('Open blank form') }}</a>
                            </li>
                        @endforelse
                    </ul>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No employees in this company.') }}</li>
            @endforelse
        </ul>
    </aside>

    <div class="min-w-0 flex-1">
        @if ($employee)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm text-gray-700">
                    <span class="font-semibold text-gray-900">{{ $employee->full_name }}</span>
                    @if ($examination)
                        &middot; {{ __('Examination on :date', ['date' => $examination->examined_on->format('d-m-Y')]) }}
                        @if ($examination->signed_copy_path)
                            &middot; <a href="{{ route('compliance.examinations.signed-copy', $examination) }}" target="_blank" class="text-brand-600 hover:underline">{{ __('Signed copy') }}</a>
                        @endif
                    @else
                        &middot; <span class="text-amber-700">{{ __('No examination recorded — the form prints blank.') }}</span>
                    @endif
                </div>
                @if ($examination)
                    <div class="flex items-center gap-2">
                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'examination-edit')" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Edit') }}</button>
                        <form method="POST" action="{{ route('compliance.examinations.destroy', $examination) }}" data-confirm="{{ __('Delete this examination? It is removed from Form 5, 27-A, 30 and 32.') }}">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">{{ __('Delete') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
        <div class="overflow-x-auto rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="sheet">
                @include('compliance.forms.sheets.'.$view)
            </div>
        </div>
    </div>
</div>

<x-modal name="examination-add" :show="old('_modal') === 'examination-add'" max-width="2xl">
    @include('compliance.forms._examination-form', ['modal' => 'examination-add', 'examination' => null])
</x-modal>
@if ($examination)
    <x-modal name="examination-edit" :show="old('_modal') === 'examination-edit'" max-width="2xl">
        @include('compliance.forms._examination-form', ['modal' => 'examination-edit'])
    </x-modal>
@endif
