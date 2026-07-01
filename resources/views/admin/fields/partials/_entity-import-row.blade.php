@php
    $rowValues = $rowValues ?? [];
    $isPrototype = $isPrototype ?? false;
    $displayIndex = $isPrototype ? '__INDEX__' : $rowIndex;
    $baseName = "{$name}[{$displayIndex}]";
    $variable = old("{$baseName}.variable", $rowValues['variable'] ?? '');
    $plugin = old("{$baseName}.plugin", $rowValues['plugin'] ?? '');
    $function = old("{$baseName}.function", $rowValues['function'] ?? '');
    $parameters = old("{$baseName}.parameters", $rowValues['parameters'] ?? []);
    $parameters = is_array($parameters) ? $parameters : [];
    $selectedPlugin = collect($pluginsFunctionsCatalog)->firstWhere('identifier', (string) $plugin);
    $functions = is_array($selectedPlugin['functions'] ?? null) ? $selectedPlugin['functions'] : [];
    $selectedFunction = is_array($functions[$function] ?? null) ? $functions[$function] : null;
    $functionParameters = is_array($selectedFunction['parameters'] ?? null) ? $selectedFunction['parameters'] : [];
    $pageUrlPlaceholders = is_array($pageUrlPlaceholders ?? null) ? $pageUrlPlaceholders : [];
    $collapseId = "{$repeaterId}-import-{$displayIndex}";
@endphp

<div class="loom-repeater__item loom-entity-import-row border rounded p-3 mb-3"
     data-entity-import-row
     data-index="{{ $displayIndex }}">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div class="fw-medium">{{ $itemLabel }} @unless($isPrototype)#{{ is_numeric($displayIndex) ? $displayIndex + 1 : '' }}@endunless</div>
        @if (! $disabled)
            <button type="button"
                    class="btn btn-sm btn-outline-danger"
                    data-entity-imports-remove
                    aria-label="Remove import">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label form-label-sm" for="{{ $collapseId }}-variable">Variable</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="text"
                       class="form-control"
                       id="{{ $collapseId }}-variable"
                       name="{{ $baseName }}[variable]"
                       value="{{ ltrim((string) $variable, '$') }}"
                       placeholder="productDetails"
                       data-entity-import-variable
                       @disabled($disabled)>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label form-label-sm" for="{{ $collapseId }}-plugin">Plugin</label>
            <select class="form-select form-select-sm"
                    id="{{ $collapseId }}-plugin"
                    name="{{ $baseName }}[plugin]"
                    data-entity-import-plugin
                    @disabled($disabled)>
                <option value="" disabled @selected($plugin === '')>Select a plugin…</option>
                @foreach ($pluginsFunctionsCatalog as $catalogPlugin)
                    <option value="{{ $catalogPlugin['identifier'] }}" @selected((string) $plugin === (string) ($catalogPlugin['identifier'] ?? ''))>
                        {{ $catalogPlugin['label'] ?? $catalogPlugin['identifier'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label form-label-sm" for="{{ $collapseId }}-function">Function</label>
            <select class="form-select form-select-sm"
                    id="{{ $collapseId }}-function"
                    name="{{ $baseName }}[function]"
                    data-entity-import-function
                    @disabled($disabled || $plugin === '')>
                <option value="" disabled @selected($function === '')>Select a function…</option>
                @php
                    $modelFunctions = collect($functions)->filter(fn ($def) => (bool) ($def['builtin'] ?? false));
                    $customFunctions = collect($functions)->filter(fn ($def) => ! ($def['builtin'] ?? false));
                @endphp
                @if ($modelFunctions->isNotEmpty())
                    <optgroup label="Model functions">
                        @foreach ($modelFunctions as $functionKey => $functionDefinition)
                            <option value="{{ $functionKey }}" @selected((string) $function === (string) $functionKey)>
                                {{ $functionDefinition['label'] ?? $functionKey }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
                @if ($customFunctions->isNotEmpty())
                    <optgroup label="Custom functions">
                        @foreach ($customFunctions as $functionKey => $functionDefinition)
                            <option value="{{ $functionKey }}" @selected((string) $function === (string) $functionKey)>
                                {{ $functionDefinition['label'] ?? $functionKey }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
        </div>
    </div>

    <div data-entity-import-parameters>
        @if ($functionParameters !== [])
            <div class="small text-muted mb-2">Function parameters</div>
            <div class="row g-3">
                @foreach ($functionParameters as $parameter)
                    @php
                        $paramName = $parameter['name'] ?? '';
                        $paramLabel = $parameter['label'] ?? $paramName;
                        $isDynamicParam = (bool) ($parameter['dynamic'] ?? false);
                        $paramValue = $parameters[$paramName] ?? [];
                        $paramValue = is_array($paramValue) ? $paramValue : ['mode' => 'static', 'value' => $paramValue];
                        $paramMode = (string) ($paramValue['mode'] ?? 'static');
                    @endphp
                    <div class="col-12" data-entity-import-parameter="{{ $paramName }}">
                        <label class="form-label form-label-sm">{{ $paramLabel }}</label>
                        @if ($isDynamicParam)
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm"
                                            name="{{ $baseName }}[parameters][{{ $paramName }}][mode]"
                                            data-entity-import-param-mode
                                            @disabled($disabled)>
                                        <option value="static" @selected($paramMode === 'static')>Static value</option>
                                        <option value="path_param" @selected(in_array($paramMode, ['path_param', 'route_param'], true))>Path parameter (/{name})</option>
                                        <option value="query_param" @selected($paramMode === 'query_param')>Query parameter (?name=)</option>
                                        @if ($paramMode === 'url_segment')
                                            <option value="url_segment" selected>URL segment (legacy)</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <div data-entity-import-param-static @if ($paramMode !== 'static') hidden @endif>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               name="{{ $baseName }}[parameters][{{ $paramName }}][value]"
                                               value="{{ $paramValue['value'] ?? '' }}"
                                               placeholder="Static value"
                                               @disabled($disabled)>
                                    </div>
                                    <div data-entity-import-param-path @if (! in_array($paramMode, ['path_param', 'route_param'], true)) hidden @endif>
                                        @if ($pageUrlPlaceholders === [])
                                            <p class="text-muted small mb-0">Add <code>{name}</code> placeholders to the page URL above.</p>
                                        @else
                                            <select class="form-select form-select-sm"
                                                    name="{{ $baseName }}[parameters][{{ $paramName }}][param]"
                                                    data-entity-import-path-param
                                                    @disabled($disabled)>
                                                <option value="" disabled @selected(! in_array($paramValue['param'] ?? '', $pageUrlPlaceholders, true))>Select parameter…</option>
                                                @foreach ($pageUrlPlaceholders as $placeholder)
                                                    <option value="{{ $placeholder }}" @selected(($paramValue['param'] ?? '') === $placeholder)>{{ $placeholder }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                    <div data-entity-import-param-query @if ($paramMode !== 'query_param') hidden @endif>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               name="{{ $baseName }}[parameters][{{ $paramName }}][param]"
                                               value="{{ $paramValue['param'] ?? '' }}"
                                               placeholder="Query parameter name"
                                               @disabled($disabled)>
                                    </div>
                                    <div data-entity-import-param-url-segment @if ($paramMode !== 'url_segment') hidden @endif>
                                        <input type="number"
                                               class="form-control form-control-sm"
                                               name="{{ $baseName }}[parameters][{{ $paramName }}][segment]"
                                               value="{{ $paramValue['segment'] ?? 1 }}"
                                               min="1"
                                               placeholder="Segment index"
                                               @disabled($disabled)>
                                    </div>
                                </div>
                            </div>
                        @else
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="{{ $baseName }}[parameters][{{ $paramName }}][value]"
                                   value="{{ is_array($paramValue) ? ($paramValue['value'] ?? '') : $paramValue }}"
                                   @disabled($disabled)>
                            <input type="hidden" name="{{ $baseName }}[parameters][{{ $paramName }}][mode]" value="static">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
