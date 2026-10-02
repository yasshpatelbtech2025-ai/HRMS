@props(['active'])

<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
    {{ $active ? __('Active') : __('Inactive') }}
</span>
