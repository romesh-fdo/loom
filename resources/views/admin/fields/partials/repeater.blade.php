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
    $items = $items ?? ['layout' => [], 'fields' => []];
    $minItems = (int) ($minItems ?? 0);
    $maxItems = isset($maxItems) ? (int) $maxItems : null;
    $addLabel = $addLabel ?? 'Add item';
    $itemLabel = $itemLabel ?? 'Item';
    $repeaterId = $id;

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
    <div class="loom-repeater"
         id="{{ $repeaterId }}"
         data-repeater
         data-name="{{ $name }}"
         data-item-label="{{ $itemLabel }}"
         data-min="{{ $minItems }}"
         @if ($maxItems !== null) data-max="{{ $maxItems }}" @endif
         @if ($disabled) data-disabled="true" @endif>
        @if (! $disabled)
            <button type="button"
                    class="loom-repeater__add loom-form-btn loom-form-btn--secondary"
                    data-repeater-add>
                <i class="bi bi-plus-lg"></i>
                {{ $addLabel }}
            </button>
        @endif

        <div class="loom-repeater__items" data-repeater-items @if ($value === []) hidden @endif>
            @foreach ($value as $rowIndex => $rowValues)
                @include('admin.fields.partials._repeater-row', [
                    'name' => $name,
                    'repeaterId' => $repeaterId,
                    'rowIndex' => $rowIndex,
                    'rowValues' => is_array($rowValues) ? $rowValues : [],
                    'items' => $items,
                    'itemLabel' => $itemLabel,
                    'disabled' => $disabled,
                    'minItems' => $minItems,
                ])
            @endforeach
        </div>

        @if (! $disabled)
            <template data-repeater-prototype>
                @include('admin.fields.partials._repeater-row', [
                    'name' => $name,
                    'repeaterId' => $repeaterId,
                    'rowIndex' => 0,
                    'rowValues' => [],
                    'items' => $items,
                    'itemLabel' => $itemLabel,
                    'disabled' => false,
                    'minItems' => $minItems,
                    'isPrototype' => true,
                ])
            </template>
        @endif
    </div>
@endcomponent
