<x-app-layout :title="__('Designations')">
    <x-ui.page-header :title="__('Designations')" :description="__('Job titles shared across all companies.')">
        <x-slot:actions>
            <x-ui.button type="button" icon="plus" @click="$dispatch('open-modal', 'add-position')">{{ __('Add designation') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="positions" :paginator="$positions" class="max-w-3xl">
        <x-slot:filters>
            <form method="GET" class="relative w-full sm:w-64" role="search">
                <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search designations…') }}" aria-label="{{ __('Search designations') }}" class="control h-8 py-1 ps-8 text-13">
            </form>
        </x-slot:filters>

        <x-slot:head>
            <x-ui.th sort="name" col="name">{{ __('Designation') }}</x-ui.th>
            <x-ui.th sort="employees" col="employees" align="end">{{ __('Employees') }}</x-ui.th>
            <th class="w-12 border-b border-line"><span class="sr-only">{{ __('Actions') }}</span></th>
        </x-slot:head>

        @foreach ($positions as $position)
            <tr>
                <td data-col="name"><a href="{{ route('positions.edit', $position) }}" class="font-medium text-ink hover:text-primary">{{ $position->name }}</a></td>
                <td data-col="employees" class="text-end font-mono tabular text-ink-2">
                    <a href="{{ route('employees.index', ['position_id' => $position->id]) }}" class="hover:text-primary hover:underline">{{ $position->employees_count }}</a>
                </td>
                <td class="text-end">
                    <x-ui.row-actions :label="__('Actions for :name', ['name' => $position->name])">
                        <x-ui.menu-item :href="route('positions.edit', $position)" icon="pencil">{{ __('Rename') }}</x-ui.menu-item>
                        <div class="my-1 border-t border-line"></div>
                        <x-ui.menu-item danger icon="trash"
                            :confirm="['title' => __('Delete :name?', ['name' => $position->name]), 'message' => __('Only designations nobody holds can be deleted. You can undo this right after.'), 'confirmLabel' => __('Delete designation'), 'action' => route('positions.destroy', $position)]">{{ __('Delete') }}</x-ui.menu-item>
                    </x-ui.row-actions>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-ui.empty-state icon="users" :title="request('search') ? __('No designations match') : __('No designations yet')" :message="__('For example Helper, Operator, Supervisor.')">
                <x-ui.button type="button" icon="plus" @click="$dispatch('open-modal', 'add-position')">{{ __('Add designation') }}</x-ui.button>
            </x-ui.empty-state>
        </x-slot:empty>
    </x-ui.data-table>

    {{-- Quick add without leaving the list --}}
    <x-ui.modal name="add-position" :title="__('Add designation')" :show="$errors->has('name')">
        <form method="POST" action="{{ route('positions.store') }}" class="p-5">
            @csrf
            <x-ui.field :label="__('Designation name')" for="name" required>
                <x-ui.input name="name" required maxlength="255" x-init="$watch('show', v => v && $nextTick(() => $el.focus()))" />
            </x-ui.field>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button variant="secondary" type="button" @click="show = false">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button>{{ __('Add designation') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</x-app-layout>
