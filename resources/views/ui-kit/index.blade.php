@php
    $swatches = [
        'Surfaces' => ['canvas', 'surface', 'sunken', 'rail', 'line', 'line-strong'],
        'Text' => ['ink', 'ink-2', 'ink-3'],
        'Brand & status' => ['primary', 'primary-soft', 'success', 'success-soft', 'warn', 'warn-soft', 'danger', 'danger-soft', 'info', 'info-soft'],
    ];
@endphp

<x-app-layout title="Components">
    <x-ui.page-header title="Components" description="The design system every page is built from. Visible only in the local environment." :crumbs="[]">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('ui-kit.attendance')" icon="calendar">Attendance grid prototype</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-2">
        <x-ui.card title="Colour tokens" description="CSS variables in resources/css/app.css. Dark theme swaps the same names.">
            <div class="space-y-4">
                @foreach ($swatches as $group => $names)
                    <div>
                        <p class="mb-2 text-xs font-medium text-ink-3">{{ $group }}</p>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                            @foreach ($names as $n)
                                <div>
                                    <div class="h-10 rounded-control border border-line" style="background: rgb(var(--{{ $n }}))"></div>
                                    <p class="mt-1 font-mono text-[11px] text-ink-3">{{ $n }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Type scale" description="IBM Plex Sans for UI, IBM Plex Mono for IDs and numbers.">
            <div class="space-y-2">
                <p class="text-2xl font-semibold">30 · Page hero</p>
                <p class="text-xl font-semibold">24 · Page title</p>
                <p class="text-lg font-medium">20 · Section</p>
                <p class="text-base">16 · Lead text</p>
                <p class="text-sm">14 · Body (default)</p>
                <p class="text-13">13 · Tables, labels</p>
                <p class="text-xs text-ink-3">12 · Hints, meta</p>
                <p class="font-mono text-sm">EMP-0042 · ABCDE1234F · ₹ 18,500.00</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Buttons">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="button" icon="plus">Primary</x-ui.button>
                <x-ui.button type="button" variant="secondary">Secondary</x-ui.button>
                <x-ui.button type="button" variant="ghost">Ghost</x-ui.button>
                <x-ui.button type="button" variant="danger" icon="trash">Danger</x-ui.button>
                <x-ui.button type="button" variant="danger-ghost">Danger ghost</x-ui.button>
                <x-ui.button type="button" size="sm">Small</x-ui.button>
                <x-ui.button type="button" disabled>Disabled</x-ui.button>
                <x-ui.button type="button" aria-busy="true">Saving</x-ui.button>
                <x-ui.button type="button" variant="ghost" square aria-label="More"><x-icon name="dots" class="size-4" /></x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Badges & feedback">
            <div class="flex flex-wrap gap-2">
                <x-ui.badge>Neutral</x-ui.badge>
                <x-ui.badge tone="primary">Primary</x-ui.badge>
                <x-ui.badge tone="success" dot>Active</x-ui.badge>
                <x-ui.badge tone="warn">No UAN</x-ui.badge>
                <x-ui.badge tone="danger" dot>Overdue</x-ui.badge>
                <x-ui.badge tone="info">On leave</x-ui.badge>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" @click="$store.toasts.push({ message: 'Employee updated.' })">Show toast</x-ui.button>
                <x-ui.button type="button" variant="secondary" size="sm" @click="$store.toasts.push({ type: 'error', message: 'This department still has employees.' })">Show error toast</x-ui.button>
                <x-ui.button type="button" variant="secondary" size="sm" @click="$dispatch('confirm', { title: 'Delete Ravi Patel?', message: 'They will be removed from all lists. You can undo this right after.', confirmLabel: 'Delete employee', action: '#' })">Confirm dialog</x-ui.button>
                <x-ui.button type="button" variant="secondary" size="sm" @click="$dispatch('open-modal', 'demo')">Modal</x-ui.button>
                <x-ui.menu align="start">
                    <x-slot:trigger><x-ui.button type="button" variant="secondary" size="sm" icon-right="chevron-down">Menu</x-ui.button></x-slot:trigger>
                    <x-ui.menu-item icon="pencil">Edit</x-ui.menu-item>
                    <x-ui.menu-item icon="download">Export</x-ui.menu-item>
                    <div class="my-1 border-t border-line"></div>
                    <x-ui.menu-item icon="trash" danger>Delete</x-ui.menu-item>
                </x-ui.menu>
            </div>
            <x-ui.modal name="demo" title="Example modal">
                <div class="p-5 text-sm text-ink-2">Modals trap focus, close on Esc or outside click, and slide up as a sheet on phones.</div>
            </x-ui.modal>
        </x-ui.card>

        <x-ui.card title="Form controls" class="xl:col-span-2">
            <form class="grid gap-5 sm:grid-cols-3" novalidate onsubmit="return false">
                <x-ui.field label="Text" for="kit_text" hint="Hints sit under the field."><x-ui.input name="kit_text" placeholder="Placeholder" /></x-ui.field>
                <x-ui.field label="With error" for="kit_err" required>
                    <x-ui.input name="kit_err" value="12345" data-rules="digits:10" data-label="Mobile number" x-init="$nextTick(() => $el.dispatchEvent(new Event('focusout', { bubbles: true })))" />
                </x-ui.field>
                <x-ui.field label="Money" for="kit_money"><x-ui.input name="kit_money" prefix="₹" mono inputmode="decimal" value="18500" /></x-ui.field>
                <x-ui.field label="Mobile (masked)" for="kit_mobile"><x-ui.input name="kit_mobile" mono x-mask="99999 99999" placeholder="98765 43210" /></x-ui.field>
                <x-ui.field label="Aadhaar (masked)" for="kit_aadhaar"><x-ui.input name="kit_aadhaar" mono x-mask="9999 9999 9999" placeholder="0000 0000 0000" /></x-ui.field>
                <x-ui.field label="PAN" for="kit_pan"><x-ui.input name="kit_pan" mono class="uppercase" x-mask="aaaaa9999a" placeholder="ABCDE1234F" data-rules="pan" data-label="PAN" /></x-ui.field>
                <x-ui.field label="Searchable select" for="kit_combo">
                    <x-ui.combobox name="kit_combo" placeholder="Search…" :options="collect(['Production', 'Packing', 'Stores', 'Maintenance', 'Quality', 'Dispatch', 'Security'])->map(fn ($l, $i) => ['value' => $i + 1, 'label' => $l])->all()" />
                </x-ui.field>
                <x-ui.field label="Native select" for="kit_select"><x-ui.select name="kit_select" placeholder="Select" :options="['single' => 'Single', 'married' => 'Married']" /></x-ui.field>
                <x-ui.field label="Date" for="kit_date"><x-ui.input name="kit_date" type="date" /></x-ui.field>
            </form>
        </x-ui.card>

        <x-ui.card title="Headline numbers">
            <div class="grid grid-cols-2 gap-3">
                <x-ui.stat-card label="Total employees" value="412" hint="Active on rolls" href="#" />
                <x-ui.stat-card label="Today present" pending href="#" />
            </div>
        </x-ui.card>

        <x-ui.card title="Empty state & loading" :padded="false">
            <x-ui.empty-state icon="users" title="No employees yet" message="Add employees one by one. Each employee belongs to a client company.">
                <x-ui.button type="button" icon="plus">Add employee</x-ui.button>
            </x-ui.empty-state>
            <div class="border-t border-line"><x-ui.skeleton type="rows" :count="3" /></div>
            <div class="border-t border-line p-5"><x-ui.skeleton :count="3" /></div>
        </x-ui.card>
    </div>
</x-app-layout>
