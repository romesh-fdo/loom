@php
    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? '');
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'form-control d-none';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
    $rows = $rows ?? 12;
    $language = $attributes['data-language'] ?? 'html';
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help'
))
    <textarea
        rows="{{ $rows }}"
        @include('admin.fields.partials._attributes')
    >{{ $value }}</textarea>
    <div class="code-editor-mount"
         data-code-editor
         data-target="{{ $id }}"
         data-language="{{ $language }}"
         @if ($disabled) data-disabled="true" @endif
         @if ($readonly) data-readonly="true" @endif></div>
@endcomponent
