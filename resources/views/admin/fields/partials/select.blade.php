@php
    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? '');
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'form-select';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
    $options = $options ?? [];

    $normalizedOptions = [];
    foreach ($options as $optionKey => $optionValue) {
        if (is_array($optionValue)) {
            $normalizedOptions[] = [
                'value' => $optionValue['value'] ?? $optionKey,
                'label' => $optionValue['label'] ?? $optionValue['value'] ?? $optionKey,
            ];
        } else {
            $normalizedOptions[] = [
                'value' => is_string($optionKey) ? $optionKey : $optionValue,
                'label' => $optionValue,
            ];
        }
    }
@endphp

@php
    $emptyOptionLabel = $placeholder;
    $placeholder = null;
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help'
))
    <select @include('admin.fields.partials._attributes')>
        @if ($emptyOptionLabel)
            <option value="" disabled {{ $value === '' || $value === null ? 'selected' : '' }}>{{ $emptyOptionLabel }}</option>
        @endif
        @foreach ($normalizedOptions as $option)
            <option value="{{ $option['value'] }}" @selected((string) $value === (string) $option['value'])>
                {{ $option['label'] }}
            </option>
        @endforeach
    </select>
@endcomponent
