@props([
    'name',
    'label',
    'value' => null,
    'type' => 'text',
    'required' => false,
    'wide' => false,
    'step' => null,
    'min' => null,
])

<div @class(['entry-field-wide' => $wide])>
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        @if($step !== null) step="{{ $step }}" @endif
        @if($min !== null) min="{{ $min }}" @endif
        @required($required)
        class="form-control"
    >
</div>
