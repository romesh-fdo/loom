@php
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'form-control';
    $placeholder = $placeholder ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
    $errorKey = $errorKey ?? preg_replace('/\[(.*?)\]/', '.$1', $name);
@endphp
id="{{ $id }}"
name="{{ $name }}"
class="{{ $class }}@error($errorKey) is-invalid @enderror"
@if ($placeholder) placeholder="{{ $placeholder }}" @endif
@if ($required) required @endif
@if ($disabled) disabled @endif
@if ($readonly) readonly @endif
@foreach ($attributes as $attrKey => $attrValue)
@if (is_bool($attrValue))
@if ($attrValue) {{ $attrKey }} @endif
@else
{{ $attrKey }}="{{ $attrValue }}"
@endif
@endforeach
