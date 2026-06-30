@php
    $rowValues = $rowValues ?? [];
    $blockSlug = old("{$name}.{$rowIndex}.block_slug", $rowValues['block_slug'] ?? $rowValues['block_id'] ?? '');
    $values = old("{$name}.{$rowIndex}.values", $rowValues['values'] ?? []);
    $values = is_array($values) ? $values : [];
    $isPrototype = $isPrototype ?? false;
    $displayIndex = $isPrototype ? '__INDEX__' : $rowIndex;
    $baseName = "{$name}[{$displayIndex}]";
    $repeaterId = $repeaterId ?? 'block-repeater';
    $collapseId = "{$repeaterId}-block-{$displayIndex}-body";
    $selectedBlock = collect($blocksCatalog ?? [])->firstWhere('slug', (string) $blockSlug);
    $blockParameters = is_array($selectedBlock['parameters'] ?? null) ? $selectedBlock['parameters'] : [];
    $blockLabel = $selectedBlock['name'] ?? ($itemLabel ?? 'Block');
    $isExpanded = false;
@endphp

<div @class([
    'loom-repeater__item',
    'loom-block-repeater__item',
]) data-block-repeater-item data-index="{{ $displayIndex }}">
    <div class="loom-block-repeater__header">
        <button type="button"
                @class([
                    'loom-block-repeater__toggle',
                    'collapsed' => ! $isExpanded,
                ])
                data-bs-toggle="collapse"
                data-bs-target="#{{ $collapseId }}"
                aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                aria-controls="{{ $collapseId }}"
                data-block-repeater-toggle
                aria-label="Toggle {{ $blockLabel }} settings">
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="loom-block-repeater__picker">
            <i class="bi bi-box-seam loom-block-repeater__picker-icon" aria-hidden="true"></i>
            <div class="loom-block-repeater__picker-field">
                <span class="loom-block-repeater__picker-label">Block type</span>
                <select class="form-select loom-block-repeater__picker-select"
                        name="{{ $name }}[{{ $displayIndex }}][block_slug]"
                        data-block-repeater-select
                        @if ($required) required @endif
                        @if ($disabled) disabled @endif>
                    <option value="" disabled {{ $blockSlug === '' || $blockSlug === null ? 'selected' : '' }}>Select a block…</option>
                    @foreach ($blocksCatalog as $block)
                        <option value="{{ $block['slug'] }}" @selected((string) $blockSlug === (string) $block['slug'])>
                            {{ $block['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        @if (! $disabled)
            <button type="button"
                    class="btn btn-sm btn-outline-danger loom-repeater__remove loom-block-repeater__remove"
                    data-block-repeater-remove
                    aria-label="Remove block"
                    title="Remove block">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        @endif
    </div>
    <div id="{{ $collapseId }}"
         @class([
             'collapse',
             'loom-block-repeater__collapse',
             'show' => $isExpanded,
         ])
         data-block-repeater-collapse>
        <div class="loom-block-repeater__body">
            <div class="loom-block-repeater__parameters"
                 data-block-repeater-parameters
                 data-initial-values='@json($values)'>
                @if ($blockSlug !== '' && $blockSlug !== null && count($blockParameters) > 0)
                    @include('admin.fields.partials._block-parameters', [
                        'parameters' => $blockParameters,
                        'baseName' => $baseName,
                        'values' => $values,
                        'disabled' => $disabled,
                    ])
                @endif
            </div>
        </div>
    </div>
</div>
