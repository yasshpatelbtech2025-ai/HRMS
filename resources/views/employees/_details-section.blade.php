{{-- A titled block of label: value pairs on the employee details sheet. Expects: $title, $rows. --}}
<section class="mb-5 break-inside-avoid">
    <h2 class="mb-3 border-b border-line pb-1.5 font-semibold text-ink">{{ $title }}</h2>
    <dl class="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2 print:grid-cols-2">
        @foreach ($rows as $label => $value)
            <div class="flex min-w-0 flex-col gap-0.5 sm:flex-row sm:gap-2 print:flex-row print:gap-2">
                <dt class="shrink-0 text-ink-2 sm:w-44 print:w-44">{{ $label }}:</dt>
                <dd class="min-w-0 break-words text-ink">{{ filled($value) ? $value : '—' }}</dd>
            </div>
        @endforeach
    </dl>
</section>
