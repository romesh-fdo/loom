@php
    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? '');
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'loom-rich-text-source';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help'
))
    <div class="loom-rich-text-field">
        <textarea
            hidden
            @include('admin.fields.partials._attributes')
        >{{ $value }}</textarea>

        <div class="loom-rich-text-editor"
             data-rich-text-editor
             data-target="{{ $id }}"
             @if ($disabled) data-disabled="true" @endif
             @if ($readonly) data-readonly="true" @endif></div>
    </div>
@endcomponent
