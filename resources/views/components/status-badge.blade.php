@props(['active'])

<x-ui.badge :tone="$active ? 'success' : 'neutral'" dot>{{ $active ? __('Active') : __('Inactive') }}</x-ui.badge>
