@php
    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? '');
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'form-check-input';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
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

<div class="{{ $wrapperClass }}">
    @if ($label)
        <p class="{{ $labelClass }}">
            {{ $label }}
            @if ($required)<span class="text-danger">*</span>@endif
        </p>
    @endif

    @foreach ($normalizedOptions as $index => $option)
        @php
            $optionId = $id . '-' . $index;
            $optionLabelClass = 'form-check-label';
        @endphp
        <div class="form-check">
            <input
                type="radio"
                id="{{ $optionId }}"
                name="{{ $name }}"
                value="{{ $option['value'] }}"
                class="{{ $class }}@error($name) is-invalid @enderror"
                @checked((string) $value === (string) $option['value'])
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
            >
            <label class="{{ $optionLabelClass }}" for="{{ $optionId }}">{{ $option['label'] }}</label>
        </div>
    @endforeach

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
