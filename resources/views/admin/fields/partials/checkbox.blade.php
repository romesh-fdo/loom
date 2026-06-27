@php
    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? false);
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'form-check-input';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-check-label';
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
    $checked = $checked ?? filter_var($value, FILTER_VALIDATE_BOOLEAN);
    $layout = 'check';
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help', 'layout'
))
    <input
        type="checkbox"
        value="1"
        @checked($checked)
        @include('admin.fields.partials._attributes')
    >
@endcomponent
