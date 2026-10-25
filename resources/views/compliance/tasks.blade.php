@use('App\Services\Compliance\ComplianceTasks')
<x-app-layout>
    <x-slot name="header">{{ __('Compliance') }}</x-slot>

    <x-section-tabs section="compliance" />

    @php
        $statuses = [
            'overdue' => ['label' => __('Overdue'), 'badge' => 'bg-danger-soft text-danger', 'accent' => 'bg-danger'],
            'week' => ['label' => __('Due this week'), 'badge' => 'bg-warn-soft text-warn', 'accent' => 'bg-warn'],
            'upcoming' => ['label' => __('Upcoming'), 'badge' => 'bg-info-soft text-info', 'accent' => 'bg-info'],
            'completed' => ['label' => __('Completed'), 'badge' => 'bg-success-soft text-success', 'accent' => 'bg-success'],
        ];
        $select = 'control-inline';
        $filter = fn (array $change) => route('compliance.to-do', array_filter(array_merge(request()->only(['company', 'status', 'department', 'type']), $change)));
    @endphp

    <h2 class="mb-4 text-2xl font-bold text-gray-900">{{ __('To Do List') }}</h2>

    {{-- Summary cards: click one to filter by it. --}}
    <x-stat-strip :items="[
        ['label' => __('Total tasks'), 'value' => $stats['total'], 'href' => $filter(['status' => 'all']), 'active' => $status === 'all'],
        ['label' => __('Overdue'), 'value' => $stats['overdue'], 'tone' => $stats['overdue'] ? 'danger' : null, 'href' => $filter(['status' => 'overdue']), 'active' => $status === 'overdue'],
        ['label' => __('Due this week'), 'value' => $stats['week'], 'tone' => $stats['week'] ? 'warn' : null, 'href' => $filter(['status' => 'week']), 'active' => $status === 'week'],
        ['label' => __('Upcoming'), 'value' => $stats['upcoming'], 'href' => $filter(['status' => 'upcoming']), 'active' => $status === 'upcoming'],
        ['label' => __('Completed'), 'value' => $stats['completed'], 'tone' => 'success', 'href' => $filter(['status' => 'completed']), 'active' => $status === 'completed'],
    ]" />

    <div class="card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <form method="GET" action="{{ route('compliance.to-do') }}" class="flex flex-wrap items-center gap-2">
                <select name="status" class="{{ $select }}" aria-label="{{ __('Status') }}" onchange="this.form.submit()">
                    <option value="all" @selected($status === 'all')>{{ __('All statuses') }}</option>
                    <option value="pending" @selected($status === 'pending')>{{ __('Pending') }}</option>
                    @foreach ($statuses as $key => $s)<option value="{{ $key }}" @selected($status === $key)>{{ $s['label'] }}</option>@endforeach
                </select>
                <select name="department" class="{{ $select }}" aria-label="{{ __('Department') }}" onchange="this.form.submit()">
                    <option value="">{{ __('Select Department') }}</option>
                    @foreach (config('compliance.task_departments') as $d)<option @selected(request('department') === $d)>{{ $d }}</option>@endforeach
                </select>
                <select name="type" class="{{ $select }} max-w-72" aria-label="{{ __('Title') }}" onchange="this.form.submit()">
                    <option value="">{{ __('Select Timeline Title') }}</option>
                    @foreach ($types as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach
                </select>
                <select name="company" class="{{ $select }}" aria-label="{{ __('Company') }}" onchange="this.form.submit()">
                    <option value="">{{ __('All Companies') }}</option>
                    @foreach ($companies as $option)<option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>@endforeach
                </select>
                <a href="{{ route('compliance.to-do') }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Reset') }}</a>
            </form>
            <div class="flex items-center gap-2">
                <a href="{{ $filter(['refresh' => 1]) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken" title="{{ __('Check again what is due and done') }}">{{ __('Refresh') }}</a>
                <x-add-button modal="add-task">{{ __('Add task') }}</x-add-button>
            </div>
        </div>

        <div class="overflow-x-auto" x-data="bulkSelect">
            <x-bulk-bar :action="route('compliance.tasks.bulk')" noun="task|tasks"
                        :actions="['complete' => [__('Complete'), 'default', null], 'reopen' => [__('Reopen'), 'default', null], 'delete' => [__('Delete'), 'danger', 'Delete :count selected task(s)? Deleted tasks are not created again.']]" />
            <table class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="w-10 px-3 py-3"><input type="checkbox" class="h-4 w-4 rounded border-field text-primary focus:ring-primary/30" :checked="allOnPage()" :indeterminate="someOnPage()" x-on:change="toggleAll($event.target.checked)" aria-label="{{ __('Select all') }}"></th>
                        <th class="px-3 py-3">{{ __('ID') }}</th><th class="px-3 py-3">{{ __('Title') }}</th><th class="px-3 py-3">{{ __('Category') }} / {{ __('Department') }}</th>
                        <th class="px-3 py-3">{{ __('Company') }}</th><th class="px-3 py-3">{{ __('Due Date') }}</th>
                        <th class="px-3 py-3">{{ __('Priority') }}</th><th class="px-3 py-3">{{ __('Status') }}</th><th class="px-3 py-3">{{ __('Assigned To') }}</th>
                    </tr>
                </thead>
                @forelse ($groups as $group)
                    @php $first = $group->first(); $grouped = $first->employee_id !== null; $number = sprintf('#%03d', $loop->iteration); @endphp
                    <tbody class="divide-y divide-gray-100 border-b border-gray-100" x-data="{ open: {{ $grouped && $group->count() > 1 ? 'false' : 'true' }} }">
                        @if ($grouped && $group->count() > 1)
                            @php
                                $counts = $group->countBy(fn ($t) => $t->status());
                                $worst = collect(['overdue', 'week', 'upcoming', 'completed'])->first(fn ($s) => isset($counts[$s]));
                            @endphp
                            <tr class="cursor-pointer bg-gray-50/60 hover:bg-gray-100" x-on:click="open = ! open">
                                <td class="px-3 py-3" x-on:click.stop>@php $groupIds = $group->pluck('uuid')->values(); @endphp<input type="checkbox" class="h-4 w-4 rounded border-field text-primary focus:ring-primary/30" :checked="groupState(@js($groupIds)) === 'all'" :indeterminate="groupState(@js($groupIds)) === 'some'" x-on:change="toggleIds(@js($groupIds), $event.target.checked)" aria-label="{{ __('Select the whole group') }}"></td>
                                <td class="px-3 py-3 font-semibold text-gray-900">{{ $number }}</td>
                                <td class="px-3 py-3 font-semibold text-gray-900" colspan="3">
                                    <span x-text="open ? '▾' : '▸'" class="me-1 text-gray-500">▸</span>{{ config('compliance.tasks.'.$first->type.'.title', $first->title) }}
                                    <span class="ms-2 text-xs font-normal text-gray-500">{{ trans_choice(':count employee|:count employees', $group->count()) }} · {{ $first->company->label }}</span>
                                </td>
                                <td class="px-3 py-3 text-gray-700">{{ $group->min('due_on')->format('d M Y') }}</td>
                                <td class="px-3 py-3"></td>
                                <td class="px-3 py-3" colspan="2">
                                    @foreach ($statuses as $key => $s)
                                        @isset ($counts[$key])<span class="me-1 inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium {{ $s['badge'] }}">{{ $counts[$key] }} {{ $s['label'] }}</span>@endisset
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                        @foreach ($group as $task)
                            @php $status = $task->status(); $link = ComplianceTasks::link($task); @endphp
                            <tr x-show="open" @if ($grouped && $group->count() > 1) style="display: none;" @endif class="align-top" :class="selected.includes(@js($task->uuid)) && 'bg-brand-50/60'">
                                <td class="px-3 py-3"><input type="checkbox" class="h-4 w-4 rounded border-field text-primary focus:ring-primary/30" value="{{ $task->uuid }}" x-model="selected" data-bulk-id aria-label="{{ __('Select :title', ['title' => $task->title]) }}"></td>
                                <td class="px-3 py-3 text-gray-500">{{ $grouped && $group->count() > 1 ? $loop->iteration : $number }}</td>
                                <td class="min-w-[15rem] px-3 py-3">
                                    <p class="font-semibold text-gray-900">{{ $task->title }}</p>
                                    @if ($task->employee)<p class="text-xs text-gray-500">{{ $task->employee->employee_code }} - {{ $task->employee->full_name }}</p>@endif
                                    @if ($task->notes)<p class="text-xs text-gray-500">{{ $task->notes }}</p>@endif
                                    <div class="mt-1 flex flex-wrap items-center gap-1 -ms-2">
                                        @if ($link)
                                            <a href="{{ $link }}" class="rounded-md px-2 py-1 text-xs font-semibold text-brand-600 hover:bg-brand-50">{{ __('Open') }}</a>
                                        @endif
                                        <form method="POST" action="{{ route($task->completed_at ? 'compliance.tasks.reopen' : 'compliance.tasks.complete', $task) }}">
                                            @csrf
                                            <button class="rounded-md px-2 py-1 text-xs font-semibold {{ $task->completed_at ? 'text-gray-600 hover:bg-gray-100' : 'text-success hover:bg-success-soft' }}">{{ $task->completed_at ? __('Reopen') : __('Complete') }}</button>
                                        </form>
                                        @can('delete-records')
                                            <form method="POST" action="{{ route('compliance.tasks.destroy', $task) }}" data-confirm="{{ __('Delete this task? It will not come back.') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-danger-soft">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                                <td class="px-3 py-3"><p class="text-gray-900">{{ $task->category }}</p><p class="text-xs text-gray-500">{{ $task->department }}</p></td>
                                <td class="px-3 py-3 text-gray-700">{{ $task->company->label }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <p class="font-semibold text-gray-900">{{ $task->due_on->format('d M Y') }}</p>
                                    @if ($status === 'overdue')<p class="text-xs font-medium text-red-600">{{ trans_choice(':count day overdue|:count days overdue', (int) $task->due_on->diffInDays(today())) }}</p>@endif
                                    @if ($status === 'week')<p class="text-xs font-medium text-warn">{{ $task->due_on->isToday() ? __('Due today') : __('Due soon') }}</p>@endif
                                </td>
                                <td class="px-3 py-3 text-gray-700">{{ \App\Models\ComplianceTask::PRIORITIES[$task->priority] ?? $task->priority }}</td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statuses[$status]['badge'] }}">{{ $statuses[$status]['label'] }}</span>
                                    @if ($task->completed_at)
                                        <p class="mt-1 text-xs text-gray-500">{{ $task->completed_at->format('d M Y') }} · {{ $task->completer?->name ?? __('automatically') }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <form method="POST" action="{{ route('compliance.tasks.update', $task) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="assigned_to" onchange="this.form.submit()" class="{{ $select }} w-36 py-1" aria-label="{{ __('Assigned To') }}">
                                            <option value="">{{ __('Unassigned') }}</option>
                                            @foreach ($users as $user)<option value="{{ $user->id }}" @selected($task->assigned_to === $user->id)>{{ $user->name }}</option>@endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tbody><x-empty-state colspan="9" icon="search">{{ ($status !== 'all' || request()->hasAny(['department', 'type'])) ? __('No tasks match these filters.') : __('Nothing is due. Tasks appear here as employees join and months close.') }}</x-empty-state></tbody>
                @endforelse
            </table>
        </div>
        <p class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
            {{ __('Tasks are created automatically: for each new employee (Form 33 fitness, I-card, police verification - due 15 days after they are added), each month (salary by the 7th; PF, ESIC and PT returns by the 15th when payroll deducted them) and each half year (Form 32 health register, when anyone works on a hazardous process). They complete themselves when the HRMS shows they are done: a medical examination, an ID card assigned, a signed police verification / PF / PT return uploaded, the salary generated.') }}
        </p>
    </div>

    <x-modal name="add-task" :show="old('_modal') === 'add-task'" max-width="lg">
        <form method="POST" action="{{ route('compliance.tasks.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="add-task">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Add task') }}</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-select-field name="company" :label="__('Company')" required :value="$company?->uuid ?? $companies->first()?->uuid" :options="$companies->mapWithKeys(fn ($c) => [$c->uuid => $c->label])->all()" />
                </div>
                <div class="sm:col-span-2"><x-field name="title" :label="__('Title')" required /></div>
                <x-select-field name="category" :label="__('Category')" required :options="array_combine(config('compliance.task_categories'), config('compliance.task_categories'))" />
                <x-select-field name="department" :label="__('Department')" required :options="array_combine(config('compliance.task_departments'), config('compliance.task_departments'))" />
                <x-field name="due_on" type="date" :label="__('Due date')" required />
                <x-select-field name="priority" :label="__('Priority')" required value="high" :options="\App\Models\ComplianceTask::PRIORITIES" />
                <div class="sm:col-span-2"><x-select-field name="assigned_to" :label="__('Assigned to')" :placeholder="__('Unassigned')" :options="$users->pluck('name', 'id')->all()" /></div>
                <div class="sm:col-span-2"><x-field name="notes" :label="__('Notes')" /></div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
