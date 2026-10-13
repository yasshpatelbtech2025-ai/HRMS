@php $title = $position->exists ? $position->name : __('Add designation'); @endphp
<x-app-layout :title="$title">
    <x-ui.page-header :title="$title" />

    <form method="POST" action="{{ $position->exists ? route('positions.update', $position) : route('positions.store') }}" data-guard class="max-w-xl pb-20">
        @csrf
        @if ($position->exists) @method('PUT') @endif
        <x-ui.card>
            <x-ui.field :label="__('Designation name')" for="name" required>
                <x-ui.input name="name" :value="$position->name" required autofocus maxlength="255" />
            </x-ui.field>
        </x-ui.card>
        <x-ui.form-actions :cancel="route('positions.index')" :submit="$position->exists ? __('Save changes') : __('Add designation')" />
    </form>
</x-app-layout>
