@php
    $allowedTypes = [
        'text', 'email', 'password', 'number', 'textarea', 'select',
        'checkbox', 'radio', 'date', 'datetime-local', 'file', 'hidden', 'code', 'dynamic_code', 'color', 'repeater', 'block_repeater',
    ];
    $namePrefix = $namePrefix ?? null;
    $fieldValues = $fieldValues ?? [];
    $rowIndex = $rowIndex ?? null;
    $repeaterId = $repeaterId ?? null;
@endphp

@foreach ($layout ?? [] as $row)
    <div @class([
        'loom-form-row' => !empty($formScope),
        $row['rowClass'] ?? 'row g-3 mb-3',
    ])>
        @foreach ($row['fields'] ?? [] as $fieldRef)
            @php
                $fieldName = is_string($fieldRef) ? $fieldRef : ($fieldRef['name'] ?? null);
                $colClass = is_array($fieldRef)
                    ? ($fieldRef['colClass'] ?? ($fields[$fieldName]['colClass'] ?? 'col-12'))
                    : ($fields[$fieldName]['colClass'] ?? 'col-12');
                $field = $fields[$fieldName] ?? null;
                $type = $field['type'] ?? $field['input'] ?? 'text';
                $inputName = $namePrefix ? "{$namePrefix}[{$fieldName}]" : $fieldName;
                $errorKey = preg_replace('/\[(.*?)\]/', '.$1', $inputName);
                $fieldValue = $fieldValues[$fieldName] ?? ($field['value'] ?? '');
                $fieldId = $field['id'] ?? 'field-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $fieldName);

                if ($namePrefix !== null && $rowIndex !== null) {
                    $fieldId = ($repeaterId ?? 'field').'-'.$rowIndex.'-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $fieldName);
                }

                $fieldConfig = $field ? [
                    'name' => $inputName,
                    'errorKey' => $errorKey,
                    'value' => old($errorKey, $fieldValue),
                    'id' => $fieldId,
                    'type' => $type,
                    'label' => $field['label'] ?? null,
                    'class' => $field['class'] ?? null,
                    'wrapperClass' => $field['wrapperClass'] ?? 'mb-0',
                    'labelClass' => $field['labelClass'] ?? 'form-label',
                    'placeholder' => $field['placeholder'] ?? null,
                    'help' => $field['help'] ?? null,
                    'required' => $field['required'] ?? false,
                    'disabled' => $field['disabled'] ?? false,
                    'readonly' => $field['readonly'] ?? false,
                    'attributes' => $field['attributes'] ?? [],
                    'options' => $field['options'] ?? [],
                    'rows' => $field['rows'] ?? null,
                    'layout' => $field['layout'] ?? 'default',
                    'items' => $field['items'] ?? null,
                    'minItems' => $field['minItems'] ?? null,
                    'maxItems' => $field['maxItems'] ?? null,
                    'addLabel' => $field['addLabel'] ?? null,
                    'itemLabel' => $field['itemLabel'] ?? null,
                    'blocksCatalog' => $field['blocksCatalog'] ?? ($blocksCatalog ?? []),
                ] : null;
            @endphp

            @if ($fieldConfig && in_array($type, $allowedTypes, true))
                <div @class([$colClass, 'loom-form-field' => !empty($formScope)])>
                    @includeIf("admin.fields.partials.{$type}", $fieldConfig)
                </div>
            @elseif ($fieldName)
                <div @class([$colClass, 'loom-form-field' => !empty($formScope)])>
                    <div class="alert alert-warning mb-0" role="alert">
                        Unknown or missing field <strong>{{ $fieldName }}</strong>.
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
