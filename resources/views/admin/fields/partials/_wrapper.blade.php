@php
    $name = $name ?? '';
    $label = $label ?? null;
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $layout = $layout ?? 'default';
    $required = $required ?? false;
    $help = $help ?? null;
    $errorKey = $errorKey ?? preg_replace('/\[(.*?)\]/', '.$1', $name);
@endphp

@if ($layout === 'check')
    <div class="{{ $wrapperClass }}">
        <div class="form-check">
            {{ $slot }}
            @if ($label)
                <label class="{{ $labelClass }}" for="{{ $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name) }}">
                    {{ $label }}
                    @if ($required)<span class="text-danger">*</span>@endif
                </label>
            @endif
            @error($errorKey)
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            @if ($help)
                <div class="form-text">{{ $help }}</div>
            @endif
        </div>
    </div>
@else
    <div class="{{ $wrapperClass }}">
        @if ($label)
            <label class="{{ $labelClass }}" for="{{ $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name) }}">
                {{ $label }}
                @if ($required)<span class="text-danger">*</span>@endif
            </label>
        @endif
        {{ $slot }}
        @error($errorKey)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($help)
            <div class="form-text">{{ $help }}</div>
        @endif
    </div>
@endif
