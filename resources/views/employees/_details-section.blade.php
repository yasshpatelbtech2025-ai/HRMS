{{-- A titled block of label: value pairs on the employee details sheet. Expects: $title, $rows. --}}
<section class="mb-5 break-inside-avoid">
    <h2 class="mb-2 border-b border-gray-200 pb-1 font-semibold text-gray-900">{{ $title }}</h2>
    <dl class="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2 print:grid-cols-2">
        @foreach ($rows as $label => $value)
            <div class="flex gap-2">
                <dt class="w-44 shrink-0 text-gray-600">{{ $label }}:</dt>
                <dd class="text-gray-900">{{ filled($value) ? $value : '—' }}</dd>
            </div>
        @endforeach
    </dl>
</section>
