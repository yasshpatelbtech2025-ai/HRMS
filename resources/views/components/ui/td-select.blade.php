@props(['value', 'label'])
<td class="w-10 px-3">
    <x-ui.checkbox x-model="selected" value="{{ $value }}" aria-label="{{ __('Select :name', ['name' => $label]) }}" />
</td>
