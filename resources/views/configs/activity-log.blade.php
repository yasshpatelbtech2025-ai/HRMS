<x-app-layout>
    <x-slot name="header">{{ __('Configs') }}</x-slot>

    <x-section-tabs section="configs" />

    @php
        $select = 'control-inline';
        $badges = [
            'created' => 'bg-success-soft text-success', 'updated' => 'bg-warn-soft text-warn', 'deleted' => 'bg-danger-soft text-danger',
            'purged' => 'bg-danger-soft text-danger', 'restored' => 'bg-success-soft text-success', 'sign-in-failed' => 'bg-danger-soft text-danger',
            'locked-out' => 'bg-danger-soft text-danger',
        ];
        $show = fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_bool($value) ? ($value ? __('Yes') : __('No')) : (string) ($value ?? '—'));
    @endphp

    <div>
        <div class="mb-4">
            <h1 class="page-head text-2xl font-semibold text-ink">{{ __('Activity log') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Who created, changed, deleted, exported or signed in, and when. Entries are kept for :days days. Passwords and ID numbers are never shown.', ['days' => config('access.activity_retention_days')]) }}</p>
        </div>
        <div class="card">

        <form method="GET" action="{{ route('configs.activity-log') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <label class="text-xs font-medium text-gray-600">{{ __('Person') }}
                <select name="user" class="mt-1 block {{ $select }}">
                    <option value="">{{ __('Everyone') }}</option>
                    @foreach ($users as $user)<option value="{{ $user->id }}" @selected(($filters['user'] ?? null) == $user->id)>{{ $user->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-medium text-gray-600">{{ __('Company') }}
                <select name="company" class="mt-1 block {{ $select }}">
                    <option value="">{{ __('All companies') }}</option>
                    @foreach ($companies as $company)<option value="{{ $company->id }}" @selected(($filters['company'] ?? null) == $company->id)>{{ $company->establishment_name }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-medium text-gray-600">{{ __('Activity') }}
                <select name="event" class="mt-1 block {{ $select }}">
                    <option value="">{{ __('Everything') }}</option>
                    @foreach (\App\Models\ActivityLog::EVENTS as $key => $label)<option value="{{ $key }}" @selected(($filters['event'] ?? null) === $key)>{{ __($label) }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-medium text-gray-600">{{ __('From') }}
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block {{ $select }}">
            </label>
            <label class="text-xs font-medium text-gray-600">{{ __('To') }}
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block {{ $select }}">
            </label>
            <label class="text-xs font-medium text-gray-600">{{ __('Record') }}
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Name or title') }}" class="mt-1 block {{ $select }}">
            </label>
            <x-primary-button>{{ __('Filter') }}</x-primary-button>
            @if (array_filter($filters))
                <a href="{{ route('configs.activity-log') }}" class="py-2 text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('Clear') }}</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-5 py-3">{{ __('When') }}</th>
                        <th class="px-5 py-3">{{ __('Who') }}</th>
                        <th class="px-5 py-3">{{ __('Activity') }}</th>
                        <th class="px-5 py-3">{{ __('Record') }}</th>
                        <th class="px-5 py-3">{{ __('Company') }}</th>
                        <th class="px-5 py-3">{{ __('Details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($entries as $entry)
                        <tr class="align-top hover:bg-gray-50">
                            <td class="whitespace-nowrap px-5 py-3 text-gray-700" title="{{ $entry->created_at->format('d M Y H:i:s') }}">
                                {{ $entry->created_at->format('d M Y H:i') }}
                                <p class="text-xs text-gray-500">{{ $entry->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $entry->user?->name ?? __('System') }}</p>
                                @if ($entry->ip_address)<p class="text-xs text-gray-500">{{ $entry->ip_address }}</p>@endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badges[$entry->event] ?? 'bg-gray-100 text-gray-700' }}">{{ __(\App\Models\ActivityLog::EVENTS[$entry->event] ?? $entry->event) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($entry->subjectLabel())<p class="text-xs text-gray-500">{{ __($entry->subjectLabel()) }}</p>@endif
                                <p class="text-gray-900">{{ $entry->description ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $entry->company?->establishment_name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @if ($entry->changes)
                                    <details class="text-xs">
                                        <summary class="cursor-pointer font-medium text-brand-700">{{ __('Show') }}</summary>
                                        <dl class="mt-2 max-w-md space-y-1">
                                            @if (isset($entry->changes['old']) || isset($entry->changes['new']))
                                                @foreach ($entry->changes['new'] ?? [] as $field => $new)
                                                    <div class="break-words">
                                                        <dt class="inline font-semibold text-gray-700">{{ \Illuminate\Support\Str::headline($field) }}:</dt>
                                                        <dd class="inline text-gray-600">
                                                            @if (array_key_exists($field, $entry->changes['old'] ?? []))<span class="text-danger line-through">{{ $show($entry->changes['old'][$field]) }}</span> → @endif
                                                            <span class="text-gray-900">{{ $show($new) }}</span>
                                                        </dd>
                                                    </div>
                                                @endforeach
                                            @else
                                                @foreach ($entry->changes as $field => $value)
                                                    <div class="break-words"><dt class="inline font-semibold text-gray-700">{{ \Illuminate\Support\Str::headline($field) }}:</dt> <dd class="inline text-gray-600">{{ is_array($value) ? implode(', ', $value) : $show($value) }}</dd></div>
                                                @endforeach
                                            @endif
                                        </dl>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" icon="inbox" :title="__('No activity')">{{ __('Nothing matches these filters.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$entries" />
    </div>
    </div>
</x-app-layout>
