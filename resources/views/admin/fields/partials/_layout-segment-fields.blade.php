@php
    $baseName = $baseName ?? '';
    $segmentPath = $segmentPath ?? '';
    $parameters = $parameters ?? [];
    $values = is_array($values ?? null) ? $values : [];
    $disabled = $disabled ?? false;
    $entityImports = is_array($entityImports ?? null) ? $entityImports : [];
    $pluginsFunctionsCatalog = is_array($pluginsFunctionsCatalog ?? null) ? $pluginsFunctionsCatalog : [];
    $layoutFieldErrors = is_array($layoutFieldErrors ?? null) ? $layoutFieldErrors : [];
    $hasEntityImports = $entityImports !== [];

    if ($segmentPath === '' && preg_match('/\[([^\]]+)\]$/', $baseName, $segmentMatch)) {
        $segmentPath = $segmentMatch[1];
    }
@endphp

@if ($parameters === [])
    <p class="text-muted small mb-0">This segment has no configurable fields.</p>
@else
    @unless ($hasEntityImports)
        <div class="alert alert-info py-2 small mb-3" role="alert">
            Add at least one entity import before using dynamic layout fields.
        </div>
    @endunless

    <div class="table-responsive">
        <table class="table table-sm align-middle loom-layout-fields-table mb-0">
            <thead>
                <tr>
                    <th scope="col" class="loom-layout-fields-table__field-col">Field</th>
                    <th scope="col" class="loom-layout-fields-table__mode-col">Mode</th>
                    <th scope="col">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($parameters as $parameter)
                    @php
                        $paramName = $parameter['name'] ?? '';
                        $paramLabel = $parameter['label'] ?? $paramName;
                        $paramTip = trim((string) ($parameter['tip'] ?? ''));
                        $rawValue = $values[$paramName] ?? ($parameter['default'] ?? '');
                        $isDynamic = false;
                        $staticValue = is_scalar($rawValue) ? (string) $rawValue : '';
                        $dynamicImport = '';
                        $dynamicField = '';
                        $mode = 'static';

                        if (is_array($rawValue)) {
                            $postedMode = (string) ($rawValue['_mode'] ?? '');

                            if ($postedMode === 'dynamic' || ($postedMode !== 'static' && \Loom\Support\ThemeContent\PageLayoutFieldsComposer::isDynamicField($rawValue))) {
                                $isDynamic = true;
                                $mode = 'dynamic';
                                $dynamicImport = (string) ($rawValue['import'] ?? \Loom\Support\ThemeContent\PageLayoutFieldsComposer::splitDynamicPath((string) ($rawValue['dynamic'] ?? ''))[0]);
                                $dynamicField = (string) ($rawValue['field'] ?? \Loom\Support\ThemeContent\PageLayoutFieldsComposer::splitDynamicPath((string) ($rawValue['dynamic'] ?? ''))[1]);
                            } elseif (array_key_exists('static', $rawValue)) {
                                $staticValue = is_scalar($rawValue['static']) ? (string) $rawValue['static'] : '';
                            } elseif (array_key_exists('value', $rawValue)) {
                                $staticValue = is_scalar($rawValue['value']) ? (string) $rawValue['value'] : '';
                            }
                        }
                        $staticFieldName = "{$baseName}[{$paramName}][static]";
                        $importFieldName = "{$baseName}[{$paramName}][import]";
                        $fieldFieldName = "{$baseName}[{$paramName}][field]";
                        $modeFieldName = "{$baseName}[{$paramName}][_mode]";
                        $fieldId = preg_replace('/[^a-zA-Z0-9_-]/', '-', "{$baseName}-{$paramName}");
                        $paramType = \Loom\Support\MediaParameterProcessor::resolveEffectiveType($parameter, $staticValue);
                        $selectedImport = collect($entityImports)->first(fn ($import) => is_array($import) && ($import['variable'] ?? '') === $dynamicImport);
                        $selectedPlugin = collect($pluginsFunctionsCatalog)->firstWhere('identifier', (string) ($selectedImport['plugin'] ?? ''));
                        $functions = is_array($selectedPlugin['functions'] ?? null) ? $selectedPlugin['functions'] : [];
                        $selectedFunction = is_array($functions[$selectedImport['function'] ?? ''] ?? null) ? $functions[$selectedImport['function'] ?? ''] : null;
                        $returnFields = is_array($selectedFunction['returns'] ?? null) ? $selectedFunction['returns'] : [];
                        $fieldErrors = array_values(array_filter(
                            $layoutFieldErrors,
                            fn (string $message) => str_contains($message, '"'.$segmentPath.'.'.$paramName.'"')
                        ));
                        $fieldHasError = $fieldErrors !== [];
                    @endphp
                    <tr class="loom-layout-field @if ($fieldHasError) table-danger @endif" data-layout-field="{{ $paramName }}">
                        <td>
                            <div class="fw-medium">{{ $paramLabel }}</div>
                            @if ($paramTip !== '')
                                <div class="form-text mb-0">{{ $paramTip }}</div>
                            @endif
                            <div class="text-muted small">{{ $paramName }}</div>
                        </td>
                        <td>
                            <select class="form-select form-select-sm loom-layout-field-mode @if ($fieldHasError) is-invalid @endif"
                                    name="{{ $modeFieldName }}"
                                    data-layout-field-mode
                                    @disabled($disabled)>
                                <option value="static" @selected($mode === 'static')>Static</option>
                                <option value="dynamic" @selected($mode === 'dynamic') @disabled(! $hasEntityImports)>Dynamic</option>
                            </select>
                        </td>
                        <td>
                            <div class="loom-layout-field-static" data-layout-field-static @if ($mode === 'dynamic') hidden @endif>
                                @includeIf("admin.fields.partials.{$paramType}", [
                                    'name' => $staticFieldName,
                                    'label' => null,
                                    'value' => $staticValue,
                                    'placeholder' => $parameter['placeholder'] ?? null,
                                    'required' => $parameter['required'] ?? false,
                                    'disabled' => $disabled,
                                    'wrapperClass' => 'mb-0',
                                    'labelClass' => 'form-label',
                                    'class' => 'form-control form-control-sm'.($fieldHasError ? ' is-invalid' : ''),
                                    'options' => $parameter['options'] ?? [],
                                    'rows' => $parameter['rows'] ?? null,
                                ])
                            </div>

                            <div class="loom-layout-field-dynamic" data-layout-field-dynamic @if ($mode === 'static') hidden @endif>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm mb-1" for="{{ $fieldId }}-import">Import</label>
                                        <select class="form-select form-select-sm @if ($fieldHasError) is-invalid @endif"
                                                id="{{ $fieldId }}-import"
                                                name="{{ $importFieldName }}"
                                                data-layout-field-import
                                                @disabled($disabled)>
                                            <option value="" disabled @selected($dynamicImport === '')>Select import…</option>
                                            @foreach ($entityImports as $import)
                                                @php $importVariable = (string) ($import['variable'] ?? ''); @endphp
                                                @if ($importVariable !== '')
                                                    <option value="{{ $importVariable }}" @selected($dynamicImport === $importVariable)>
                                                        ${{ $importVariable }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm mb-1" for="{{ $fieldId }}-field">Field</label>
                                        <select class="form-select form-select-sm @if ($fieldHasError) is-invalid @endif"
                                                id="{{ $fieldId }}-field"
                                                name="{{ $fieldFieldName }}"
                                                data-layout-field-return
                                                @disabled($disabled)>
                                            <option value="" disabled @selected($dynamicField === '')>Select field…</option>
                                            @foreach ($returnFields as $returnField)
                                                @php $returnName = (string) ($returnField['name'] ?? ''); @endphp
                                                @if ($returnName !== '')
                                                    <option value="{{ $returnName }}" @selected($dynamicField === $returnName)>
                                                        {{ $returnField['label'] ?? $returnName }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
