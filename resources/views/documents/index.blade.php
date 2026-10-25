<x-app-layout>
    <x-slot name="header">{{ __('Documents') }}</x-slot>

    @php $select = 'control-inline'; @endphp

    <div x-data="{ category: 'all', doc: null, open: false }" class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h1 class="page-head text-2xl font-semibold text-ink">{{ __('Documents') }}</h1>
            <div class="relative" x-on:click.outside="open = false">
                <button type="button" x-on:click="open = ! open" :aria-expanded="open" class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">
                    {{ __('Select Documents') }}
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                </button>
                <div x-show="open" x-transition class="absolute right-0 z-20 mt-1 max-h-96 w-80 overflow-y-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black/5" style="display: none;">
                    <button type="button" x-on:click="category = 'all'; doc = null; open = false" class="block w-full px-4 py-2 text-left font-medium hover:bg-gray-50">{{ __('All Documents') }}</button>
                    @foreach ($categories as $key => $category)
                        <button type="button" x-on:click="category = @js($key); doc = null; open = false" class="block w-full px-4 py-2 text-left font-medium hover:bg-gray-50">{{ $category['short'] }}</button>
                        @foreach ($category['slots'] as $slot)
                            <button type="button" x-on:click="category = @js($key); doc = @js($slot['slug']); open = false" class="block w-full px-8 py-1.5 text-left text-gray-600 hover:bg-gray-50">{{ $slot['name'] }}</button>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        @foreach ($categories as $key => $category)
            <section x-show="category === 'all' || category === @js($key)">
                <h3 class="mb-4 border-b border-gray-200 pb-2 text-xl font-semibold text-gray-900">{{ $category['title'] }}</h3>
                @if (empty($category['slots']))
                    <p class="text-sm text-gray-500">{{ __('Add a company to get its documents here.') }}</p>
                @endif
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($category['slots'] as $slot)
                        @php
                            $slotFiles = $files->get($slot['slug'], collect());
                            $latest = $slotFiles->first();
                        @endphp
                        <article x-show="! doc || doc === @js($slot['slug'])" class="flex flex-col rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                            <form method="POST" action="{{ route('documents.upload', $slot['slug']) }}" enctype="multipart/form-data"
                                  x-data="{ over: false }" x-on:dragover.prevent="over = true" x-on:dragleave="over = false"
                                  x-on:drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; $el.requestSubmit()">
                                @csrf
                                <label class="relative flex h-40 cursor-pointer items-center justify-center overflow-hidden rounded-md border-2 border-dashed text-center text-sm"
                                       :class="over ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-300 text-gray-500 hover:border-gray-400'">
                                    @if ($latest && $latest->isImage())
                                        <img src="{{ route('documents.file', $latest) }}" alt="{{ $latest->original_name }}" class="absolute inset-0 h-full w-full object-cover opacity-30">
                                    @endif
                                    <span class="relative">
                                        @if ($latest)
                                            <span class="block font-medium text-gray-700">{{ trans_choice(':count file|:count files', $slotFiles->count()) }}</span>
                                        @endif
                                        {{ __('Drag & Drop') }}<br>{{ __('or Click') }}
                                    </span>
                                    <input x-ref="input" type="file" name="files[]" multiple class="sr-only" onchange="this.form.requestSubmit()"
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" aria-label="{{ __('Upload :name', ['name' => $slot['name']]) }}">
                                </label>
                            </form>

                            <a href="{{ route('documents.show', $slot['slug']) }}" class="mt-3 truncate text-sm font-medium text-primary hover:underline" title="{{ $slot['name'] }}">{{ $slot['name'] }} →</a>

                            @if ($slotFiles->isNotEmpty())
                                <ul class="mt-2 space-y-1 text-xs">
                                    @foreach ($slotFiles->take(3) as $file)
                                        <li class="flex items-center justify-between gap-2">
                                            <a href="{{ route('documents.file', $file) }}" target="_blank" class="truncate text-brand-600 hover:underline">{{ $file->original_name }}</a>
                                            <span class="shrink-0 text-gray-400">{{ $file->created_at->format('d M Y') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <form method="POST" action="{{ route('documents.assign', $slot['slug']) }}" class="mt-auto flex items-center justify-center gap-2 pt-4 text-sm">
                                @csrf
                                @method('PUT')
                                <label for="assign-{{ $slot['slug'] }}" class="font-semibold text-gray-700">{{ __('Assigned To:') }}</label>
                                <select id="assign-{{ $slot['slug'] }}" name="user_id" onchange="this.form.submit()" class="w-1/2 py-1 {{ $select }}">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected(($assignments[$slot['slug']] ?? null) === $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <x-input-error :messages="array_merge($errors->get('files'), $errors->get('files.*'))" />
    </div>
</x-app-layout>
