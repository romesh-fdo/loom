@php
    $href = $href ?? '#';
    $icon = $icon ?? 'bi-arrow-right';
    $label = $label ?? '';
    $variant = $variant ?? 'default';
    $extraClass = $extraClass ?? '';
    $attributes = $attributes ?? [];
@endphp

<a href="{{ $href }}"
   @class(['admin-action', "admin-action--{$variant}", $extraClass])
   @isset($target) target="{{ $target }}" @endisset
   @isset($rel) rel="{{ $rel }}" @endisset
   @isset($ariaLabel) aria-label="{{ $ariaLabel }}" @endisset
   @foreach ($attributes as $attrKey => $attrValue)
       {{ $attrKey }}="{{ $attrValue }}"
   @endforeach>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</a>
