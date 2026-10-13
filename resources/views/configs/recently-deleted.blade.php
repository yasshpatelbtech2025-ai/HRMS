<x-app-layout>
    <x-slot name="header">{{ __('Configs') }}</x-slot>

    <x-section-tabs section="configs" />

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('Recently deleted') }}</h2>
                <p class="text-sm text-gray-500">{{ __('Restore anything deleted by mistake. Records are removed for good :days days after deletion; employees and companies are kept until restored.', ['days' => $retention]) }}</p>
            </div>
            <form method="GET" action="{{ route('configs.recently-deleted') }}">
                <select name="type" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" aria-label="{{ __('Type') }}">
                    <option value="">{{ __('Everything') }}</option>
                    @foreach ($types as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ __($label) }}</option>@endforeach
                </select>
            </form>
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Record') }}</th>
                        <th class="px-4 py-3">{{ __('Deleted') }}</th>
                        <th class="px-4 py-3">{{ __('Removed for good') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        @php $deletedAt = $row['model']->deleted_at; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><span class="inline-flex whitespace-nowrap rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ __($row['label']) }}</span></td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $row['title'] }}</p>
                                @if ($row['context'])<p class="text-xs text-gray-500">{{ $row['context'] }}</p>@endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700" title="{{ $deletedAt->format('d M Y H:i') }}">{{ $deletedAt->diffForHumans() }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $row['expires'] ? $deletedAt->copy()->addDays($retention)->format('d M Y') : __('Kept until restored') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <form method="POST" action="{{ route('recently-deleted.restore', [$row['type'], $row['model']->getKey()]) }}">
                                        @csrf
                                        <button class="rounded-md px-3 py-1.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">{{ __('Restore') }}</button>
                                    </form>
                                    @if ($row['expires'])
                                        @can('delete-records')
                                            <form method="POST" action="{{ route('recently-deleted.destroy', [$row['type'], $row['model']->getKey()]) }}"
                                                  data-confirm="{{ __('Delete :name for good? This cannot be undone.', ['name' => $row['title']]) }}" data-confirm-button="{{ __('Delete for good') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md px-3 py-1.5 text-sm font-semibold text-red-600 hover:bg-danger-soft">{{ __('Delete for good') }}</button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="5" icon="check" :title="__('Nothing deleted')">{{ __('Deleted records appear here so they can be restored.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</x-app-layout>
