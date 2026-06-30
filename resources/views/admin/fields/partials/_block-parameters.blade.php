@php
    use Loom\Support\ParameterLayout;

    $parameters = $parameters ?? [];
    $baseName = $baseName ?? '';
    $values = is_array($values ?? null) ? $values : [];
    $disabled = $disabled ?? false;
@endphp

@foreach (ParameterLayout::groupByRow($parameters) as $rowParameters)
    <div class="loom-form-row row g-3 mb-3">
                @foreach ($rowParameters as $parameter)
                    @php
                        $paramName = $parameter['name'] ?? '';
                        $paramLabel = $parameter['label'] ?? $paramName;
                        $paramTip = trim((string) ($parameter['tip'] ?? ''));
                        $value = $values[$paramName] ?? ($parameter['default'] ?? '');
                        $fieldName = "{$baseName}[values][{$paramName}]";
                        $fieldId = preg_replace('/[^a-zA-Z0-9_-]/', '-', "{$baseName}-{$paramName}");
                        $paramType = \Loom\Support\MediaParameterProcessor::resolveEffectiveType($parameter, $value);
                        $colClass = ParameterLayout::isValidColClass((string) ($parameter['colClass'] ?? ''))
                            ? $parameter['colClass']
                            : ParameterLayout::defaultColClass($paramType);
                    @endphp

            @if ($paramType === 'repeater')
                @php
                    $repeaterRows = is_array($value) ? $value : [];
                    $repeaterFields = is_array($parameter['fields'] ?? null) ? $parameter['fields'] : [];
                    $itemLabel = $parameter['item'] ?? 'Item';
                @endphp
                <div class="{{ $colClass }}">
                    <label class="form-label">{{ $paramLabel }}</label>
                    @if ($paramTip !== '')
                        <div class="form-text">{{ $paramTip }}</div>
                    @endif
                    <div class="loom-value-repeater" data-value-repeater data-parameter-name="{{ $paramName }}">
                        <div class="loom-value-repeater__items" data-value-repeater-items @if ($repeaterRows === []) hidden @endif>
                            @foreach ($repeaterRows as $repeaterIndex => $repeaterRow)
                                @php
                                    $repeaterRow = is_array($repeaterRow) ? $repeaterRow : [];
                                @endphp
                                <div class="loom-value-repeater__item" data-value-repeater-item data-index="{{ $repeaterIndex }}">
                                    <div class="loom-value-repeater__item-header">
                                        <span class="loom-value-repeater__item-label">{{ $itemLabel }} {{ $repeaterIndex + 1 }}</span>
                                        @if (! $disabled)
                                            @include('admin.partials.action-submit', [
                                                'icon' => 'bi-trash',
                                                'label' => 'Remove',
                                                'variant' => 'danger',
                                                'type' => 'button',
                                                'extraClass' => 'admin-action-submit--compact',
                                                'attributes' => ['data-value-repeater-remove' => ''],
                                            ])
                                        @endif
                                    </div>
                                    @foreach (ParameterLayout::groupByRow($repeaterFields) as $subRowFields)
                                        <div class="row g-2">
                                            @foreach ($subRowFields as $field)
                                                @php
                                                    $fieldName = $field['name'] ?? '';
                                                    $fieldType = $field['type'] ?? 'text';
                                                    $fieldLabel = $field['label'] ?? $fieldName;
                                                    $fieldValue = $repeaterRow[$fieldName] ?? ($field['default'] ?? '');
                                                    $subFieldName = "{$baseName}[values][{$paramName}][{$repeaterIndex}][{$fieldName}]";
                                                    $subFieldId = preg_replace('/[^a-zA-Z0-9_-]/', '-', "{$baseName}-{$paramName}-{$repeaterIndex}-{$fieldName}");
                                                    $subColClass = ParameterLayout::isValidColClass((string) ($field['colClass'] ?? ''))
                                                        ? $field['colClass']
                                                        : ParameterLayout::defaultColClass($fieldType);
                                                @endphp
                                                <div class="{{ $subColClass }}">
                                                    <label class="form-label form-label-sm" for="{{ $subFieldId }}">{{ $fieldLabel }}</label>
                                                    <input type="{{ in_array($fieldType, ['number', 'email', 'url', 'color'], true) ? $fieldType : 'text' }}"
                                                           class="form-control form-control-sm"
                                                           id="{{ $subFieldId }}"
                                                           name="{{ $subFieldName }}"
                                                           value="{{ $fieldValue }}"
                                                           @if ($disabled) disabled @endif>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        @if (! $disabled)
                            @include('admin.partials.action-submit', [
                                'icon' => 'bi-plus-lg',
                                'label' => 'Add '.$itemLabel,
                                'variant' => 'primary',
                                'type' => 'button',
                                'extraClass' => 'admin-action-submit--compact mt-2',
                                'attributes' => ['data-value-repeater-add' => ''],
                            ])
                        @endif
                    </div>
                </div>
            @elseif ($paramType === 'textarea' || $paramType === 'code')
                <div class="{{ $colClass }}">
                    <label class="form-label" for="{{ $fieldId }}">{{ $paramLabel }}</label>
                    <textarea class="form-control @if ($paramType === 'code') font-monospace @endif"
                              id="{{ $fieldId }}"
                              name="{{ $fieldName }}"
                              rows="{{ $paramType === 'code' ? 4 : 3 }}"
                              @if ($disabled) disabled @endif>{{ $value }}</textarea>
                    @if ($paramTip !== '')
                        <div class="form-text">{{ $paramTip }}</div>
                    @endif
                </div>
            @elseif ($paramType === 'richtext')
                @php
                    $fieldId = 'richtext-' . trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $fieldName), '-');
                @endphp
                <div class="{{ $colClass }}">
                    <label class="form-label" for="{{ $fieldId }}">{{ $paramLabel }}</label>
                    <div class="loom-rich-text-field">
                        <textarea class="loom-rich-text-source"
                                  id="{{ $fieldId }}"
                                  name="{{ $fieldName }}"
                                  hidden
                                  @if ($disabled) disabled @endif>{{ $value }}</textarea>
                        <div class="loom-rich-text-editor"
                             data-rich-text-editor
                             data-target="{{ $fieldId }}"
                             @if ($disabled) data-disabled="true" @endif></div>
                    </div>
                    @if ($paramTip !== '')
                        <div class="form-text">{{ $paramTip }}</div>
                    @endif
                </div>
            @elseif ($paramType === 'checkbox')
                <div class="{{ $colClass }}">
                    <div class="form-check mt-4">
                        <input type="hidden" name="{{ $fieldName }}" value="0">
                        <input type="checkbox"
                               class="form-check-input"
                               id="{{ $fieldId }}"
                               name="{{ $fieldName }}"
                               value="1"
                               @checked($value === true || $value === '1' || $value === 1)
                               @if ($disabled) disabled @endif>
                        <label class="form-check-label" for="{{ $fieldId }}">{{ $paramLabel }}</label>
                    </div>
                    @if ($paramTip !== '')
                        <div class="form-text">{{ $paramTip }}</div>
                    @endif
                </div>
            @elseif ($paramType === 'select')
                <div class="{{ $colClass }}">
                    <label class="form-label" for="{{ $fieldId }}">{{ $paramLabel }}</label>
                    <select class="form-select" id="{{ $fieldId }}" name="{{ $fieldName }}" @if ($disabled) disabled @endif>
                        <option value="" disabled @selected($value === '' || $value === null)>Select…</option>
                        @foreach ($parameter['options'] ?? [] as $option)
                            @php
                                $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                                $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
                            @endphp
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                    @if ($paramTip !== '')
                        <div class="form-text">{{ $paramTip }}</div>
                    @endif
                </div>
            @elseif ($paramType === 'radio')
                <div class="{{ $colClass }}">
                    <fieldset>
                        <legend class="form-label mb-1">{{ $paramLabel }}</legend>
                        @foreach ($parameter['options'] ?? [] as $optionIndex => $option)
                            @php
                                $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                                $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
                                $optionId = "{$fieldId}-{$optionIndex}";
                            @endphp
                            <div class="form-check">
                                <input type="radio"
                                       class="form-check-input"
                                       id="{{ $optionId }}"
                                       name="{{ $fieldName }}"
                                       value="{{ $optionValue }}"
                                       @checked((string) $value === (string) $optionValue)
                                       @if ($disabled) disabled @endif>
                                <label class="form-check-label" for="{{ $optionId }}">{{ $optionLabel }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                    @if ($paramTip !== '')
                        <div class="form-text">{{ $paramTip }}</div>
                    @endif
                </div>
            @elseif (in_array($paramType, ['media_selector', 'media_attach', 'media_finder'], true))
                @include('admin.fields.partials.media_parameter', [
                    'name' => $fieldName,
                    'label' => $paramLabel,
                    'type' => $paramType === 'media_finder' ? 'media_selector' : $paramType,
                    'value' => $value,
                    'help' => $paramTip !== '' ? $paramTip : null,
                    'wrapperClass' => $colClass,
                    'disabled' => $disabled,
                ])
            @elseif ($paramType === 'url')
                @include('admin.fields.partials.url_parameter', [
                    'name' => $fieldName,
                    'label' => $paramLabel,
                    'value' => $value,
                    'help' => $paramTip !== '' ? $paramTip : null,
                    'wrapperClass' => $colClass,
                    'disabled' => $disabled,
                ])
            @else
                <div class="{{ $colClass }}">
                    <label class="form-label" for="{{ $fieldId }}">{{ $paramLabel }}</label>
                    <input type="{{ in_array($paramType, ['number', 'email', 'url', 'color'], true) ? $paramType : 'text' }}"
                           class="form-control"
                           id="{{ $fieldId }}"
                           name="{{ $fieldName }}"
                           value="{{ is_scalar($value) ? $value : '' }}"
                           @if ($disabled) disabled @endif>
                    @if ($paramTip !== '')
                        <div class="form-text">{{ $paramTip }}</div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endforeach
