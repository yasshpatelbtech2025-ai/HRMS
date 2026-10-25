{{-- The "⋯" button and its menu, for a list row. Put icon links/buttons (with sr-only labels) in the slot. --}}
@props(['label' => __('Actions')])
<div x-data="menu({ align: 'end' })" class="row-menu relative inline-block" @keydown.escape.stop="close(true)" @click.outside="close()">
    <div x-ref="trigger">
        <button type="button" @click="toggle()" @keydown.down.prevent="openAndFocus()" aria-haspopup="menu" :aria-expanded="open"
                class="row-menu-trigger inline-flex size-8 items-center justify-center rounded-md text-ink-2 hover:bg-sunken hover:text-ink">
            <x-icon name="dots" class="size-5" /><span class="sr-only">{{ $label }}</span>
        </button>
    </div>
    <div x-show="open" x-ref="panel" role="menu" :style="pos" style="display: none;"
         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1"
         @keydown.down.prevent="focusNext(1)" @keydown.up.prevent="focusNext(-1)" @keydown.home.prevent="focusEdge(false)" @keydown.end.prevent="focusEdge(true)" @keydown.tab="close()"
         class="row-menu-panel fixed z-[45] w-52 rounded-lg border border-line bg-surface p-1 text-start shadow-pop">
        {{ $slot }}
    </div>
</div>
