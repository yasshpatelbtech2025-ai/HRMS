{{--
    A list row's actions, in one "⋯" menu: Edit (opens the named modal), any extra actions
    from the slot, then Delete. `editable` false hides Edit (Delete shows to admins only).
    Slot items are the usual icon links/buttons (class "rounded-md p-2" with an sr-only
    label); inside the menu their label is shown as text (see .row-menu in app.css).
--}}
@props(['editModal' => null, 'deleteUrl' => null, 'deleteConfirm' => null, 'editable' => true])

<x-row-menu>
    @if ($editable && $editModal)
        <button type="button" role="menuitem" x-on:click="close(); $dispatch('open-modal', @js($editModal))" class="rounded-md p-2">
            <x-icon name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span>
        </button>
    @endif
    {{ $slot }}
    @if ($deleteUrl)
        @can('delete-records')
            <div class="my-1 border-t border-line"></div>
            <form method="POST" action="{{ $deleteUrl }}" data-confirm="{{ $deleteConfirm }}">
                @csrf
                @method('DELETE')
                <button role="menuitem" class="row-menu-danger rounded-md p-2">
                    <x-icon name="trash" class="h-4 w-4" /><span class="sr-only">{{ __('Delete') }}</span>
                </button>
            </form>
        @endcan
    @endif
</x-row-menu>
