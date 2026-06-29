@php
    $rowValues = $rowValues ?? [];
    $blockId = old("{$name}.{$rowIndex}.block_id", $rowValues['block_id'] ?? '');
    $values = old("{$name}.{$rowIndex}.values", $rowValues['values'] ?? []);
    $values = is_array($values) ? $values : [];
    $isPrototype = $isPrototype ?? false;
    $displayIndex = $isPrototype ? '__INDEX__' : $rowIndex;
@endphp

<div @class([
    'loom-repeater__item',
    'loom-block-repeater__item',
]) data-block-repeater-item data-index="{{ $displayIndex }}">
    <div class="loom-repeater__item-header">
        <span class="loom-repeater__item-label" data-block-repeater-item-label>
            {{ $itemLabel }} {{ $isPrototype ? '' : ((int) $rowIndex + 1) }}
        </span>
        @if (! $disabled)
            <button type="button"
                    class="loom-repeater__remove"
                    data-block-repeater-remove
                    aria-label="Remove block">
                <i class="bi bi-trash"></i>
            </button>
        @endif
    </div>
    <div class="loom-repeater__item-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Block</label>
                <select class="form-select"
                        name="{{ $name }}[{{ $displayIndex }}][block_id]"
                        data-block-repeater-select
                        @if ($required) required @endif
                        @if ($disabled) disabled @endif>
                    <option value="" disabled {{ $blockId === '' || $blockId === null ? 'selected' : '' }}>Select a block…</option>
                    @foreach ($blocksCatalog as $block)
                        <option value="{{ $block['id'] }}" @selected((string) $blockId === (string) $block['id'])>
                            {{ $block['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="loom-block-repeater__parameters row g-3"
             data-block-repeater-parameters
             data-initial-values="{{ json_encode($values) }}"></div>
    </div>
</div>
