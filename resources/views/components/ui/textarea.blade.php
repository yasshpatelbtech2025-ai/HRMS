@props(['name', 'id' => null, 'value' => null, 'rows' => 3])
@php $id ??= $name; @endphp
<textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}"
          @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
          {{ $attributes->class(['control']) }}>{{ old($name, $value) }}</textarea>
