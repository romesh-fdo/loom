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
    $inputGroupPrefix = $inputGroupPrefix ?? null;
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
        @if ($inputGroupPrefix !== null && $inputGroupPrefix !== '')
            <div class="input-group">
                <span class="input-group-text">{{ $inputGroupPrefix }}</span>
                <input
                    type="{{ $type }}"
                    value="{{ $value }}"
                    @include('admin.fields.partials._attributes')
                >
            </div>
        @else
            <input
                type="{{ $type }}"
                value="{{ $value }}"
                @include('admin.fields.partials._attributes')
            >
        @endif
    @endcomponent
@endif
