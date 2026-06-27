@php
    $namePrefix = $name.'['.$rowIndex.']';
    $isPrototype = $isPrototype ?? false;
    $rowNumber = $rowIndex + 1;
@endphp

<div @class([
    'loom-repeater__item',
    'd-none' => $isPrototype,
]) data-repeater-item data-index="{{ $rowIndex }}">
    <div class="loom-repeater__item-header">
        <span class="loom-repeater__item-label" data-repeater-item-label>
            {{ $itemLabel }} {{ $isPrototype ? '' : $rowNumber }}
        </span>
        @if (! ($disabled ?? false))
            <button type="button"
                    class="loom-repeater__remove"
                    data-repeater-remove
                    aria-label="Remove {{ strtolower($itemLabel) }}">
                <i class="bi bi-trash"></i>
            </button>
        @endif
    </div>

    <div class="loom-repeater__item-body">
        @include('admin.fields.render-layout', [
            'layout' => $items['layout'] ?? [],
            'fields' => $items['fields'] ?? [],
            'formScope' => true,
            'namePrefix' => $namePrefix,
            'fieldValues' => $rowValues ?? [],
            'rowIndex' => $rowIndex,
            'repeaterId' => $repeaterId,
        ])
    </div>
</div>
