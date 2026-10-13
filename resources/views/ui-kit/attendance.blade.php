@php
    // Design prototype only: fictional people, generated statuses, nothing is saved.
    $month = \Carbon\CarbonImmutable::create(2026, 10, 1);
    $today = 13;
    $days = $month->daysInMonth;
    $holidays = [2 => 'Gandhi Jayanti'];
    $names = ['Aarav Mehta', 'Bhavna Rathod', 'Chirag Parmar', 'Disha Vaghela', 'Eshan Trivedi', 'Falguni Joshi', 'Gaurav Chauhan', 'Hetal Prajapati',
        'Ishaan Bhatt', 'Jagruti Solanki', 'Kunal Desai', 'Leena Makwana', 'Manish Patel', 'Nirali Shah', 'Om Rana', 'Pooja Dave', 'Rakesh Gohil',
        'Sejal Thakkar', 'Tushar Barot', 'Urvi Pandya', 'Vipul Raval', 'Yash Modi', 'Zalak Vyas', 'Amit Jadeja', 'Bijal Mistry', 'Darshan Panchal',
        'Ekta Chudasama', 'Harsh Dabhi', 'Jinal Kotak', 'Mitesh Sutariya', 'Nayan Rabari', 'Payal Bhavsar', 'Ronak Zala', 'Snehal Upadhyay',
        'Tejas Limbachiya', 'Vaishali Kapadia', 'Hiren Vasava', 'Komal Dodiya', 'Pratik Nai', 'Riddhi Shukla'];
    mt_srand(7);
    $rows = collect($names)->map(function ($name, $i) use ($month, $days, $today, $holidays) {
        $cells = [];
        for ($d = 1; $d <= $days; $d++) {
            $date = $month->setDay($d);
            $cells[$d] = match (true) {
                $date->isSunday() => 'W',
                isset($holidays[$d]) => 'F',
                $d > $today => '',
                $d === $today => mt_rand(0, 9) < 7 ? 'P' : (mt_rand(0, 1) ? 'M' : ''),
                default => ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', 'A', 'L', 'H', 'M'][mt_rand(0, 14)],
            };
        }

        return ['code' => sprintf('%04d', $i + 1), 'name' => $name, 'dept' => ['Production', 'Packing', 'Stores', 'Maintenance'][$i % 4], 'cells' => $cells];
    });
    $paid = ['P' => 1, 'A' => 0, 'H' => 0.5, 'L' => 1, 'W' => 1, 'F' => 1, 'M' => 0];
    $legend = ['P' => 'Present', 'A' => 'Absent', 'H' => 'Half day', 'L' => 'Leave', 'W' => 'Weekly off', 'F' => 'Holiday', 'M' => 'Missing punch'];
@endphp

<x-app-layout title="Attendance grid prototype">
    <div class="mb-4 flex items-start gap-3 rounded-card border border-warn/30 bg-warn-soft px-4 py-3 text-13 text-warn">
        <x-icon name="info" class="mt-px size-4" />
        <p><strong class="font-semibold">Design prototype.</strong> Fictional people and generated statuses, nothing is saved. This shows how the real Monthly Attendance screen will look and behave when that module is built. <a href="{{ route('ui-kit') }}" class="font-medium underline">Back to components</a></p>
    </div>

    <x-ui.page-header title="Monthly attendance" :crumbs="[['label' => 'Attendance', 'url' => route('attendance.index')]]">
        <x-slot:description>Sanand Auto Components · {{ count($rows) }} employees</x-slot:description>
        <x-slot:actions>
            <div class="flex items-center rounded-control border border-line-strong bg-surface shadow-hairline">
                <x-ui.button variant="ghost" size="sm" square type="button" aria-label="Previous month"><x-icon name="chevron-left" class="size-4" /></x-ui.button>
                <span class="px-2 text-sm font-medium tabular">{{ $month->format('F Y') }}</span>
                <x-ui.button variant="ghost" size="sm" square type="button" aria-label="Next month"><x-icon name="chevron-right" class="size-4" /></x-ui.button>
            </div>
            <x-ui.button variant="secondary" icon="download" type="button">Export</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-data="attendancePrototype" class="card overflow-hidden">
        {{-- Toolbar: legend doubles as the marking palette --}}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-line px-3 py-2.5">
            <div class="flex flex-wrap items-center gap-1" role="toolbar" aria-label="Mark selected cells">
                @foreach ($legend as $code => $label)
                    <button type="button" @mousedown.prevent @click="grid.set('{{ $code }}'); grid.focus()"
                            class="inline-flex items-center gap-1.5 rounded-control border border-line px-1.5 py-1 text-xs text-ink-2 hover:border-line-strong hover:bg-sunken"
                            title="Mark selection as {{ $label }} (key {{ $code }})">
                        <span data-s="{{ $code }}" class="flex size-5 items-center justify-center rounded font-mono text-[11px] font-semibold">{{ $code }}</span>{{ $label }}
                    </button>
                @endforeach
            </div>
            <span class="flex-1"></span>
            <x-ui.button variant="ghost" size="sm" type="button" @mousedown.prevent="" @click="grid.selectAll(); grid.fillBlanks('P')" icon="check">Fill blanks as Present</x-ui.button>
            <x-ui.button variant="ghost" size="sm" type="button" @mousedown.prevent="" @click="grid.undo(); grid.focus()" icon="undo">Undo</x-ui.button>
        </div>

        <div class="max-h-[calc(100dvh-19rem)] overflow-auto">
            <table class="att-grid w-max min-w-full" data-first-day-col="1" data-days="{{ $days }}" role="grid" aria-label="Attendance for {{ $month->format('F Y') }}">
                <thead>
                    <tr>
                        <th scope="col" class="att-name min-w-56 px-3 py-2 text-start text-xs font-medium text-ink-2">Employee</th>
                        @for ($d = 1; $d <= $days; $d++)
                            @php $date = $month->setDay($d); @endphp
                            <th scope="col" data-select-day="{{ $d }}" title="Select {{ $date->format('D j M') }}"
                                @class(['cursor-pointer px-0 py-1 text-center font-normal hover:bg-line', 'is-weekend' => $date->isSunday(), 'is-today' => $d === $today])>
                                <span class="block text-[10px] uppercase text-ink-3">{{ $date->format('D')[0] }}</span>
                                <span @class(['block font-mono text-xs', 'font-semibold text-primary' => $d === $today, 'text-ink-2' => $d !== $today])>{{ $d }}</span>
                            </th>
                        @endfor
                        <th scope="col" class="att-sum att-sum-first text-xs font-medium text-ink-2" style="inset-inline-end: 132px" title="Present">P</th>
                        <th scope="col" class="att-sum text-xs font-medium text-ink-2" style="inset-inline-end: 88px" title="Absent">A</th>
                        <th scope="col" class="att-sum text-xs font-medium text-ink-2" style="inset-inline-end: 44px" title="Leave">L</th>
                        <th scope="col" class="att-sum text-xs font-semibold text-ink" style="inset-inline-end: 0" title="Paid days">Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php $count = array_count_values(array_filter($row['cells'])); @endphp
                        <tr data-name="{{ $row['name'] }}">
                            <th scope="row" class="att-name px-3 py-1 text-start font-normal">
                                <button type="button" data-select-row class="flex w-full items-center gap-2 text-start" title="Select the whole month for {{ $row['name'] }}">
                                    <span class="font-mono text-[11px] text-ink-3">{{ $row['code'] }}</span>
                                    <span class="truncate text-13 font-medium text-ink">{{ $row['name'] }}</span>
                                    <span class="ms-auto text-[11px] text-ink-3 max-xl:hidden">{{ $row['dept'] }}</span>
                                </button>
                            </th>
                            @foreach ($row['cells'] as $d => $s)
                                @php $date = $month->setDay($d); @endphp
                                <td data-day="{{ $d }}" tabindex="-1" role="gridcell" @if ($s) data-s="{{ $s }}" @endif
                                    aria-label="{{ $date->format('j M') }}: {{ $s ? $legend[$s] : 'Not marked' }}"
                                    @class(['is-weekend' => $date->isSunday() && ! $s, 'is-today' => $d === $today])>{{ $s }}</td>
                            @endforeach
                            <td class="att-sum att-sum-first font-mono tabular text-ink-2" style="inset-inline-end: 132px" data-sum="P">{{ $count['P'] ?? 0 }}</td>
                            <td class="att-sum font-mono tabular text-ink-2" style="inset-inline-end: 88px" data-sum="A">{{ $count['A'] ?? 0 }}</td>
                            <td class="att-sum font-mono tabular text-ink-2" style="inset-inline-end: 44px" data-sum="L">{{ $count['L'] ?? 0 }}</td>
                            <td class="att-sum font-mono font-semibold tabular text-ink" style="inset-inline-end: 0" data-sum="paid">{{ collect($row['cells'])->filter()->sum(fn ($s) => $paid[$s]) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Status line + keyboard help --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-line bg-sunken/50 px-4 py-2 text-xs text-ink-3">
            <span class="font-medium text-ink-2" x-text="where || 'Click a cell to start'"></span>
            <span><kbd class="font-mono">←↑→↓</kbd> move</span>
            <span><kbd class="font-mono">Shift</kbd>+arrows or drag: select</span>
            <span><kbd class="font-mono">P A H L W F M</kbd> mark</span>
            <span><kbd class="font-mono">Del</kbd> clear</span>
            <span><kbd class="font-mono">Ctrl Z</kbd> undo</span>
            <span>Click a date or a name to select the column or row</span>
            <span class="ms-auto flex items-center gap-2" x-show="changes" x-cloak>
                <span class="text-warn"><span x-text="changes"></span> unsaved changes</span>
                <x-ui.button size="sm" type="button" @click="$store.toasts.push({ message: 'Prototype: nothing is saved.' })">Save</x-ui.button>
            </span>
        </div>
    </div>
</x-app-layout>
