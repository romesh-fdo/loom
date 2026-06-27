@php
    $name = $name ?? '';
    $label = $label ?? null;
    $rawValue = $value ?? old($name, $value ?? '');
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'd-none';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
    $language = $attributes['data-language'] ?? 'html';
    $errorKey = $errorKey ?? preg_replace('/\[(.*?)\]/', '.$1', $name);

    $parsed = is_array($rawValue) ? $rawValue : null;

    if ($parsed === null && is_string($rawValue) && $rawValue !== '') {
        $decoded = json_decode($rawValue, true);
        $parsed = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }

    if (! is_array($parsed) || ! array_key_exists('template', $parsed)) {
        $parsed = [
            'template' => is_string($rawValue) && ! str_starts_with(trim($rawValue), '{') ? $rawValue : '',
            'parameters' => [],
        ];
    }

    $parsed['parameters'] = is_array($parsed['parameters'] ?? null) ? $parsed['parameters'] : [];
    $jsonValue = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $modalId = $id . '-dynamic-modal';
    $menuId = $id . '-dynamic-menu';

    $parameterTypes = [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'number' => 'Number',
        'email' => 'Email',
        'select' => 'Select',
        'checkbox' => 'Checkbox',
        'color' => 'Color',
    ];
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help', 'errorKey'
))
    <input type="hidden"
           name="{{ $name }}"
           id="{{ $id }}"
           class="{{ $class }}"
           value="{{ $jsonValue }}"
           data-dynamic-code-input
           @if ($required) required @endif
           @if ($disabled) disabled @endif
           @if ($readonly) readonly @endif>

    <div class="dynamic-code-field"
         data-dynamic-code-editor
         data-input-id="{{ $id }}"
         data-modal-id="{{ $modalId }}"
         data-menu-id="{{ $menuId }}"
         data-language="{{ $language }}"
         @if ($disabled) data-disabled="true" @endif
         @if ($readonly) data-readonly="true" @endif>
        <div class="dynamic-code-field__editor">
            <div class="code-editor-mount dynamic-code-field__mount"
                 data-dynamic-code-mount></div>
        </div>
        <aside class="dynamic-code-parameters" data-dynamic-code-parameters>
            <div class="dynamic-code-parameters__header">
                <h6 class="dynamic-code-parameters__title">Dynamic parameters</h6>
                <p class="dynamic-code-parameters__hint">Highlight text in the editor, right-click, and choose Make dynamic.</p>
            </div>
            <div class="dynamic-code-parameters__list" data-dynamic-code-parameters-list>
                <p class="dynamic-code-parameters__empty text-muted mb-0" data-dynamic-code-parameters-empty>
                    No parameters yet.
                </p>
            </div>
        </aside>
    </div>

    <div class="dynamic-code-context-menu d-none"
         id="{{ $menuId }}"
         data-dynamic-code-context-menu
         role="menu">
        <button type="button"
                class="dynamic-code-context-menu__item"
                data-dynamic-code-make-dynamic
                role="menuitem">
            Make dynamic
        </button>
    </div>

    <div class="modal fade dynamic-code-modal"
         id="{{ $modalId }}"
         tabindex="-1"
         aria-hidden="true"
         data-dynamic-code-modal>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-dynamic-code-modal-title>Make dynamic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="{{ $id }}-param-type">Field type</label>
                        <select class="form-select" id="{{ $id }}-param-type" data-dynamic-code-param-type>
                            @foreach ($parameterTypes as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="{{ $id }}-param-label">Label</label>
                        <input type="text"
                               class="form-control"
                               id="{{ $id }}-param-label"
                               data-dynamic-code-param-label
                               autocomplete="off"
                               placeholder="e.g. Header text">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="{{ $id }}-param-name">Field name</label>
                        <input type="text"
                               class="form-control font-monospace"
                               id="{{ $id }}-param-name"
                               data-dynamic-code-param-name
                               autocomplete="off"
                               placeholder="e.g. header_text"
                               pattern="[a-z][a-z0-9_]*">
                        <div class="form-text">Lowercase letters, numbers, and underscores only.</div>
                    </div>
                    <div class="alert alert-danger mt-3 mb-0 d-none" data-dynamic-code-modal-error role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-dynamic-code-modal-submit>Add parameter</button>
                </div>
            </div>
        </div>
    </div>
@endcomponent
