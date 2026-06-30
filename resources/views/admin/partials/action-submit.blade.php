@php
    $icon = $icon ?? 'bi-check-lg';
    $label = $label ?? '';
    $variant = $variant ?? 'default';
    $type = $type ?? 'button';
    $extraClass = $extraClass ?? '';
    $buttonClass = $buttonClass ?? '';
    $attributes = $attributes ?? [];
@endphp

<button type="{{ $type }}"
        @class([
            'admin-action-submit',
            "admin-action-submit--{$variant}",
            $extraClass,
            $buttonClass,
        ])
        @foreach ($attributes as $attrKey => $attrValue)
            {{ $attrKey }}="{{ $attrValue }}"
        @endforeach>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    {{ $label }}
</button>
