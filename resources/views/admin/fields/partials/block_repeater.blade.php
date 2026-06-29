@php
    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? [];
    $value = is_array($value) ? $value : [];
    $id = $id ?? 'field-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $minItems = (int) ($minItems ?? 0);
    $maxItems = isset($maxItems) ? (int) $maxItems : null;
    $addLabel = $addLabel ?? 'Add block';
    $itemLabel = $itemLabel ?? 'Block';
    $blocksCatalog = $blocksCatalog ?? [];

    $oldValue = old($name);
    if (is_array($oldValue)) {
        $value = $oldValue;
    }

    if ($value === [] && $minItems > 0) {
        $value = array_fill(0, $minItems, []);
    }
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'wrapperClass', 'labelClass', 'required', 'help'
))
    <div class="loom-block-repeater"
         id="{{ $id }}"
         data-block-repeater
         data-name="{{ $name }}"
         data-item-label="{{ $itemLabel }}"
         data-min="{{ $minItems }}"
         @if ($maxItems !== null) data-max="{{ $maxItems }}" @endif
         @if ($disabled) data-disabled="true" @endif
         data-blocks-catalog="{{ json_encode($blocksCatalog) }}">
        @if (! $disabled)
            <button type="button"
                    class="loom-repeater__add loom-form-btn loom-form-btn--secondary"
                    data-block-repeater-add>
                <i class="bi bi-plus-lg"></i>
                {{ $addLabel }}
            </button>
        @endif

        <div class="loom-repeater__items" data-block-repeater-items @if ($value === []) hidden @endif>
            @foreach ($value as $rowIndex => $rowValues)
                @include('admin.fields.partials._block-repeater-row', [
                    'name' => $name,
                    'repeaterId' => $id,
                    'rowIndex' => $rowIndex,
                    'rowValues' => is_array($rowValues) ? $rowValues : [],
                    'blocksCatalog' => $blocksCatalog,
                    'itemLabel' => $itemLabel,
                    'disabled' => $disabled,
                    'minItems' => $minItems,
                ])
            @endforeach
        </div>

        @if (! $disabled)
            <template data-block-repeater-prototype>
                @include('admin.fields.partials._block-repeater-row', [
                    'name' => $name,
                    'repeaterId' => $id,
                    'rowIndex' => 0,
                    'rowValues' => [],
                    'blocksCatalog' => $blocksCatalog,
                    'itemLabel' => $itemLabel,
                    'disabled' => false,
                    'minItems' => $minItems,
                    'isPrototype' => true,
                ])
            </template>
        @endif
    </div>
@endcomponent
