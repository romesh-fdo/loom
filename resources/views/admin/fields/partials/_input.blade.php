@php
    $name = $name ?? '';
    $type = $type ?? 'text';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? '');
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'form-control';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
@endphp

@if ($type === 'hidden')
    <input
        type="{{ $type }}"
        value="{{ $value }}"
        @include('admin.fields.partials._attributes')
    >
@else
    @component('admin.fields.partials._wrapper', compact(
        'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help'
    ))
        <input
            type="{{ $type }}"
            value="{{ $value }}"
            @include('admin.fields.partials._attributes')
        >
    @endcomponent
@endif
