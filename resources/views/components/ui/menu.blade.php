{{-- Dropdown menu. Trigger in `trigger` slot, items in the default slot (x-ui.menu-item). --}}
@props(['align' => 'end', 'width' => 'w-48'])
<div x-data="menu({ align: @js($align) })" class="relative" @keydown.escape.stop="close(true)" @click.outside="close()" {{ $attributes }}>
    <div x-ref="trigger" @click="toggle()" @keydown.down.prevent="openAndFocus()">{{ $trigger }}</div>
    <div x-show="open" x-cloak x-ref="panel" role="menu" :style="pos"
         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75" x-transition:leave-end="opacity-0"
         @keydown.down.prevent="focusNext(1)" @keydown.up.prevent="focusNext(-1)" @keydown.home.prevent="focusEdge(false)" @keydown.end.prevent="focusEdge(true)" @keydown.tab="close()"
         class="fixed z-[45] {{ $width }} rounded-card border border-line bg-surface p-1 shadow-pop">
        {{ $slot }}
    </div>
</div>
