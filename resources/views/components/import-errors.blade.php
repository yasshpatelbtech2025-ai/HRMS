{{-- Row problems from a bulk upload (session "import_errors"); nothing was saved. --}}
@if (session('import_errors'))
    <div role="alert" class="mb-4 rounded-lg border border-danger bg-danger-soft p-4 text-sm text-danger print:hidden">
        <p class="font-semibold">{{ __('Nothing was imported. Fix these rows and upload the file again:') }}</p>
        <ul class="mt-2 list-disc space-y-1 ps-5">
            @foreach (array_slice(session('import_errors'), 0, 50) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        @if (count(session('import_errors')) > 50)
            <p class="mt-2">{{ __('…and :count more.', ['count' => count(session('import_errors')) - 50]) }}</p>
        @endif
    </div>
@endif
