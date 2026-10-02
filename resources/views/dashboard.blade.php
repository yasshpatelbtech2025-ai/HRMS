<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card :label="__('Total employees')" :value="$stats['employees']" tone="brand" />
            <x-stat-card :label="__('Today present')" :value="$stats['present']" tone="amber" />
            <x-stat-card :label="__('Today absent')" :value="$stats['absent']" tone="teal" />
            <x-stat-card :label="__('Today on leave')" :value="$stats['leave']" tone="slate" />
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat-card :label="__('Total compliances')" :value="$compliance['total']" tone="brand" />
            <x-stat-card :label="__('Pending')" :value="$compliance['pending']" tone="orange" />
            <x-stat-card :label="__('Due this week')" :value="$compliance['week']" tone="amber" />
            <x-stat-card :label="__('Overdue')" :value="$compliance['overdue']" tone="red" />
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                <h3 class="text-base font-semibold text-gray-900">{{ __('Daily attendance by department') }}</h3>
                <p class="mt-6 text-sm text-gray-500">{{ __('No attendance recorded yet.') }}</p>
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-base font-semibold text-gray-900">{{ __('Requests') }}</h3>
                <p class="mt-6 text-sm text-gray-500">{{ __('No pending attendance or leave requests.') }}</p>
            </section>
        </div>
    </div>
</x-app-layout>
