{{-- Placeholder shapes while content loads. type: lines | rows --}}
@props(['type' => 'lines', 'count' => 3, 'cols' => 5])
<div {{ $attributes->merge(['aria-hidden' => 'true']) }} role="presentation">
    @if ($type === 'rows')
        <div class="divide-y divide-line">
            @for ($i = 0; $i < $count; $i++)
                <div class="flex items-center gap-4 px-4 py-3">
                    @for ($c = 0; $c < $cols; $c++)
                        <div class="skeleton h-3" style="width: {{ [18, 28, 14, 22, 12][$c % 5] }}%"></div>
                    @endfor
                </div>
            @endfor
        </div>
    @else
        <div class="space-y-2.5">
            @for ($i = 0; $i < $count; $i++)
                <div class="skeleton h-3" style="width: {{ [92, 76, 84, 60][$i % 4] }}%"></div>
            @endfor
        </div>
    @endif
</div>
