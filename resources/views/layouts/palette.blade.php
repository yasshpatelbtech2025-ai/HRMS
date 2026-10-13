{{-- Command palette: Ctrl/Cmd+K or "/" anywhere. --}}
<div x-data="palette({ pages: @js($navigation->pages()), searchUrl: @js(route('search')) })"
     @open-palette.window="show()"
     @keydown.window.ctrl.k.prevent="open ? hide() : show()"
     @keydown.window.meta.k.prevent="open ? hide() : show()"
     @keydown.window.slash="if (!open && !['INPUT','TEXTAREA','SELECT'].includes($event.target.tagName) && !$event.target.isContentEditable) { $event.preventDefault(); show() }"
     x-show="open" x-cloak class="fixed inset-0 z-[65] flex items-start justify-center p-3 pt-[12vh]">
    <div x-show="open" x-transition.opacity.duration.100ms class="fixed inset-0 bg-ink/30" @click="hide()"></div>
    <div x-show="open" x-trap.noscroll="open" role="dialog" aria-modal="true" aria-label="{{ __('Search and jump') }}"
         x-transition:enter="ease-out duration-100" x-transition:enter-start="opacity-0 scale-[.98]"
         @keydown.escape.prevent.stop="hide()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="go()"
         class="relative w-full max-w-xl overflow-hidden rounded-card border border-line bg-surface shadow-pop">
        <div class="flex items-center gap-2.5 border-b border-line px-4">
            <x-icon name="search" class="size-[18px] text-ink-3" />
            <input x-ref="input" x-model="query" @input="onInput()" type="text" autocomplete="off" spellcheck="false"
                   role="combobox" aria-expanded="true" aria-controls="palette-list" :aria-activedescendant="'palette-' + active"
                   class="h-12 flex-1 border-0 bg-transparent p-0 text-base text-ink placeholder:text-ink-3 focus:ring-0"
                   placeholder="{{ __('Search employees, companies, or type a page name…') }}">
            <kbd class="rounded border border-line bg-sunken px-1.5 font-mono text-[11px] text-ink-3">Esc</kbd>
        </div>

        <div x-ref="list" id="palette-list" role="listbox" class="max-h-[min(26rem,60vh)] overflow-y-auto p-1.5">
            <template x-if="loading && !remote.employees.length && !remote.companies.length">
                <div class="space-y-3 px-2.5 py-3" aria-hidden="true">
                    <div class="flex items-center gap-3"><div class="skeleton size-7 rounded-full"></div><div class="skeleton h-3 w-1/3"></div></div>
                    <div class="flex items-center gap-3"><div class="skeleton size-7 rounded-full"></div><div class="skeleton h-3 w-1/2"></div></div>
                </div>
            </template>

            <template x-for="group in groups" :key="group.name">
                <div class="mb-1">
                    <p class="px-2.5 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-3" x-text="group.name"></p>
                    <template x-for="item in group.items" :key="item.url">
                        <a :href="item.url" :id="'palette-' + item.index" :data-index="item.index" role="option" :aria-selected="active === item.index"
                           @mousemove="active = item.index"
                           :class="active === item.index ? 'bg-primary-soft' : ''"
                           class="flex items-center gap-3 rounded-control px-2.5 py-2">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full"
                                  :class="item.icon === 'person' ? 'bg-sunken text-ink-2' : (item.icon === 'org' ? 'bg-info-soft text-info' : 'text-ink-3')">
                                <template x-if="item.icon === 'person'"><span class="text-xs font-semibold" x-text="item.label.slice(0, 1)"></span></template>
                                <template x-if="item.icon === 'org'"><x-icon name="building" class="size-4" /></template>
                                <template x-if="item.icon === 'page'"><x-icon name="arrow-right" class="size-4" /></template>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm text-ink" x-text="item.label"></span>
                                <span class="block truncate text-xs text-ink-3" x-show="item.section" x-text="item.section"></span>
                            </span>
                            <x-icon name="chevron-right" class="size-4 text-ink-3" x-show="active === item.index" />
                        </a>
                    </template>
                </div>
            </template>

            <div x-show="!loading && query.trim().length >= 2 && results.length === 0" class="px-3 py-8 text-center text-sm text-ink-3">
                {{ __('Nothing matches') }} “<span x-text="query"></span>”.
            </div>
        </div>

        <div class="flex items-center gap-4 border-t border-line bg-sunken/60 px-4 py-2 text-xs text-ink-3">
            <span><kbd class="font-mono">↑↓</kbd> {{ __('move') }}</span>
            <span><kbd class="font-mono">Enter</kbd> {{ __('open') }}</span>
            <span class="ms-auto"><kbd class="font-mono">Ctrl K</kbd> {{ __('or') }} <kbd class="font-mono">/</kbd> {{ __('anytime') }}</span>
        </div>
    </div>
</div>
