@props(['tabs'])

{{-- $tabs: [label => [route name, active route pattern]] --}}
<div class="mb-6 overflow-x-auto">
    <ul class="inline-flex min-w-full gap-1 rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200">
        @foreach ($tabs as $label => [$route, $pattern])
            @php $active = request()->routeIs($pattern); @endphp
            <li>
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                   class="block whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium transition {{ $active ? 'bg-brand-600 text-white' : 'text-gray-600 hover:bg-brand-50 hover:text-brand-700' }}">
                    {{ __($label) }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
