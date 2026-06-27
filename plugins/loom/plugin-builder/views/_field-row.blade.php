@php
    $field = $field ?? [];

    $validationRules = $field['validation_rules'] ?? null;

    if (! is_array($validationRules) || $validationRules === []) {
        $validation = array_values(array_filter($field['validation'] ?? [], fn ($rule) => is_string($rule) && $rule !== ''));
        $validationMessages = $field['validation_messages'] ?? [];

        if (is_array($validationMessages) && array_is_list($validationMessages)) {
            $lookup = [];

            foreach ($validationMessages as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $entryRule = trim((string) ($entry['rule'] ?? ''));

                if ($entryRule === '') {
                    continue;
                }

                $lookup[$entryRule] = (string) ($entry['message'] ?? '');
                $lookup[explode(':', $entryRule, 2)[0]] = (string) ($entry['message'] ?? '');
            }

            $validationRules = [];

            foreach ($validation as $rule) {
                $messageKey = explode(':', (string) $rule, 2)[0];
                $validationRules[] = [
                    'rule' => $rule,
                    'message' => (string) ($lookup[$rule] ?? $lookup[$messageKey] ?? ''),
                ];
            }
        } else {
            $validationRules = [];

            foreach ($validation as $rule) {
                $messageKey = explode(':', (string) $rule, 2)[0];
                $validationRules[] = [
                    'rule' => $rule,
                    'message' => (string) (is_array($validationMessages) ? ($validationMessages[$messageKey] ?? $validationMessages[$rule] ?? '') : ''),
                ];
            }
        }
    }

    $validationRules = is_array($validationRules) ? $validationRules : [];
@endphp

<div class="card plugin-builder-field" data-plugin-builder-field data-index="{{ $index }}">
    <div class="card-body">
        <div class="row g-2 align-items-end plugin-builder-field__labels">
            <div class="col-md-3">
                <label class="form-label small">Label</label>
                <input type="text" name="fields[{{ $index }}][label]" class="form-control"
                       value="{{ $field['label'] ?? '' }}" required
                       data-plugin-builder-field-label>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Field name</label>
                <input type="text" name="fields[{{ $index }}][name]" class="form-control font-monospace"
                       value="{{ $field['name'] ?? '' }}" pattern="[a-z][a-z0-9_]*" required
                       data-plugin-builder-field-name>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Type</label>
                <select name="fields[{{ $index }}][type]" class="form-select" required>
                    @foreach ($fieldTypes as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" @selected(($field['type'] ?? 'text') === $typeKey)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" data-plugin-builder-remove-field>
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        </div>

        <div class="mt-3" data-plugin-builder-validation-rules>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label small mb-0">Validation rules</label>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-plugin-builder-add-validation-rule>
                    <i class="bi bi-plus-lg"></i> Add rule
                </button>
            </div>

            <div data-plugin-builder-validation-rules-list>
                @foreach ($validationRules as $ruleIndex => $ruleEntry)
                    <div class="row g-2 mb-2 align-items-end" data-plugin-builder-validation-rule>
                        <div class="col-md-5">
                            <input type="text"
                                   name="fields[{{ $index }}][validation_rules][{{ $ruleIndex }}][rule]"
                                   class="form-control font-monospace"
                                   value="{{ $ruleEntry['rule'] ?? '' }}"
                                   placeholder="required">
                        </div>
                        <div class="col-md-6">
                            <input type="text"
                                   name="fields[{{ $index }}][validation_rules][{{ $ruleIndex }}][message]"
                                   class="form-control"
                                   value="{{ $ruleEntry['message'] ?? '' }}"
                                   placeholder="Custom message (optional)">
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-plugin-builder-remove-validation-rule>
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
