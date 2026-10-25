@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-13 text-danger']) }} data-server-error>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5"><x-icon name="alert" class="mt-px size-4 shrink-0" />{{ $message }}</li>
        @endforeach
    </ul>
@endif
