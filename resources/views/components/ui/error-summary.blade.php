{{--
    At the top of a form after a failed save: how many problems there are and a link
    to each field (fields are found by their name, so x-field ids work).
--}}
@props(['bag' => null, 'labels' => []])
@php $messages = $bag ? $errors->getBag($bag) : $errors; @endphp
@if ($messages->any())
    <div role="alert" tabindex="-1" x-data x-init="$el.focus({ preventScroll: true })"
         {{ $attributes->class(['mb-6 rounded-card border border-danger/30 bg-danger-soft px-4 py-3.5 text-sm text-ink focus:outline-none']) }}>
        <div class="flex items-start gap-3">
            <x-icon name="alert" class="mt-0.5 size-5 shrink-0 text-danger" />
            <div class="min-w-0">
                <p class="font-semibold text-danger">
                    {{ trans_choice('There is :count problem to fix before this can be saved.|There are :count problems to fix before this can be saved.', $messages->count()) }}
                </p>
                <ul class="mt-1.5 space-y-0.5 text-13 text-ink-2">
                    @foreach ($messages->messages() as $key => $list)
                        @php
                            $id = 'field-'.str_replace(['.', '_'], '-', $key);
                            $parts = explode('.', $key);
                            $inputName = array_shift($parts).collect($parts)->map(fn ($p) => "[{$p}]")->implode('');
                        @endphp
                        <li><a href="#{{ $id }}" data-id="{{ $id }}" data-name="{{ $inputName }}" class="underline decoration-ink-3/40 underline-offset-2 hover:text-ink"
                               x-on:click.prevent="const f = document.getElementById($el.dataset.id) || document.getElementsByName($el.dataset.name)[0]; if (f) { f.closest('[data-step-section]')?.dispatchEvent(new CustomEvent('form-step-show', { bubbles: true })); f.focus(); f.scrollIntoView({ block: 'center' }); }">{{ $list[0] }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
