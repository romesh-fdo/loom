@php
    use Loom\Support\ThemeContent\PageUrlPattern;

    $name = $name ?? 'entity_imports';
    $label = $label ?? 'Entity imports';
    $value = $value ?? [];
    $value = is_array($value) ? array_values($value) : [];
    $id = $id ?? 'field-entity-imports';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $help = $help ?? 'Import data from plugin functions into variables that layout fields can reference.';
    $disabled = $disabled ?? false;
    $addLabel = $addLabel ?? 'Add import';
    $itemLabel = $itemLabel ?? 'Import';
    $pluginsFunctionsCatalog = $pluginsFunctionsCatalog ?? [];
    $accordionId = $id.'-accordion';
    $repeaterId = $id.'-repeater';
    $pageUrlPlaceholders = PageUrlPattern::extractPlaceholders(
        strtolower(trim((string) old('url', $pageUrl ?? ''), '/'))
    );

    $oldValue = old($name);
    if (is_array($oldValue)) {
        $value = array_values($oldValue);
    }
@endphp

<div class="loom-entity-imports-accordion {{ $wrapperClass }}">
    <div class="accordion" id="{{ $accordionId }}">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $accordionId }}-panel"
                        aria-expanded="true"
                        aria-controls="{{ $accordionId }}-panel">
                    {{ $label }}
                </button>
            </h2>
            <div id="{{ $accordionId }}-panel"
                 class="accordion-collapse collapse show"
                 data-bs-parent="#{{ $accordionId }}">
                <div class="accordion-body">
                    @if ($help)
                        <p class="text-muted small mb-3">{{ $help }}</p>
                    @endif

                    <div class="loom-page-entity-imports"
                         id="{{ $id }}"
                         data-page-entity-imports
                         data-name="{{ $name }}"
                         @if ($disabled) data-disabled="true" @endif>
                        <script type="application/json" data-plugins-functions-catalog>@json($pluginsFunctionsCatalog)</script>
                        <script type="application/json" data-entity-imports-values>@json($value)</script>
                        <script type="application/json" data-page-url-placeholders>@json($pageUrlPlaceholders)</script>

                        <div class="loom-repeater"
                             id="{{ $repeaterId }}"
                             data-entity-imports-repeater
                             data-name="{{ $name }}"
                             data-item-label="{{ $itemLabel }}">
                            @if (! $disabled)
                                @include('admin.partials.action-submit', [
                                    'icon' => 'bi-plus-lg',
                                    'label' => $addLabel,
                                    'variant' => 'secondary',
                                    'type' => 'button',
                                    'extraClass' => 'loom-repeater__add',
                                    'attributes' => ['data-entity-imports-add' => ''],
                                ])
                            @endif

                            <div class="loom-repeater__items" data-entity-imports-items @if ($value === []) hidden @endif>
                                @foreach ($value as $rowIndex => $rowValues)
                                    @include('admin.fields.partials._entity-import-row', [
                                        'name' => $name,
                                        'repeaterId' => $repeaterId,
                                        'rowIndex' => $rowIndex,
                                        'rowValues' => is_array($rowValues) ? $rowValues : [],
                                        'pluginsFunctionsCatalog' => $pluginsFunctionsCatalog,
                                        'pageUrlPlaceholders' => $pageUrlPlaceholders,
                                        'itemLabel' => $itemLabel,
                                        'disabled' => $disabled,
                                    ])
                                @endforeach
                            </div>

                            @if (! $disabled)
                                <template data-entity-imports-prototype>
                                    @include('admin.fields.partials._entity-import-row', [
                                        'name' => $name,
                                        'repeaterId' => $repeaterId,
                                        'rowIndex' => 0,
                                        'rowValues' => [],
                                        'pluginsFunctionsCatalog' => $pluginsFunctionsCatalog,
                                        'pageUrlPlaceholders' => $pageUrlPlaceholders,
                                        'itemLabel' => $itemLabel,
                                        'disabled' => false,
                                        'isPrototype' => true,
                                    ])
                                </template>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
