<x-app-layout>
    <x-slot name="header">{{ __('Documents') }}</x-slot>

    <div class="mb-4">
        <a href="{{ route('documents.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to documents') }}</a>
    </div>

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div>
                <p class="text-13 text-ink-2">{{ $category }}</p>
                <h1 class="text-2xl font-semibold text-ink">{{ $slot['name'] }}</h1>
            </div>
            <form method="POST" action="{{ route('documents.upload', $slot['slug']) }}" enctype="multipart/form-data">
                @csrf
                <label class="cursor-pointer inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">
                    {{ __('Upload files') }}
                    <input type="file" name="files[]" multiple class="sr-only" onchange="this.form.submit()" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                </label>
            </form>
        </div>
        <div class="card">
        <x-input-error class="px-5 pt-3" :messages="array_merge($errors->get('files'), $errors->get('files.*'))" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-5 py-3">{{ __('File') }}</th>
                        <th class="px-5 py-3">{{ __('Size') }}</th>
                        <th class="px-5 py-3">{{ __('Uploaded') }}</th>
                        <th class="px-5 py-3">{{ __('Uploaded by') }}</th>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($files as $file)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('documents.file', $file) }}" target="_blank" class="font-medium text-brand-600 hover:underline">{{ $file->original_name }}</a></td>
                            <td class="px-5 py-3">{{ number_format($file->size / 1024, 0) }} KB</td>
                            <td class="px-5 py-3">{{ $file->created_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3">{{ $file->uploader?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @can('delete-records')
                                    <form method="POST" action="{{ route('documents.destroy', $file) }}" data-confirm="{{ __('Delete :name?', ['name' => $file->original_name]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm font-medium text-danger hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="5" icon="inbox">{{ __('No files uploaded yet.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</x-app-layout>
