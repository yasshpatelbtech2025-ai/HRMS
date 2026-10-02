<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />
    <x-section-tabs section="attendance.leave" nested />

    <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm ring-1 ring-gray-200">
        {{ __(':title is coming with leave applications.', ['title' => __($title)]) }}
    </div>
</x-app-layout>
