function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function parseCatalog(root) {
    const scriptEl = root.querySelector('script[data-plugins-functions-catalog]');

    if (! scriptEl?.textContent) {
        return [];
    }

    try {
        const parsed = JSON.parse(scriptEl.textContent.trim());

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function parsePageUrlPlaceholders(url) {
    const normalized = String(url ?? '').toLowerCase().trim().replace(/^\/+|\/+$/g, '');

    if (normalized === '') {
        return [];
    }

    const matches = normalized.matchAll(/\{([a-z_][a-z0-9_]*)\}/g);
    const placeholders = [];

    for (const match of matches) {
        if (! placeholders.includes(match[1])) {
            placeholders.push(match[1]);
        }
    }

    return placeholders;
}

function getPageUrlInput() {
    return document.querySelector('[data-loom-form="pages-basic"] #field-url');
}

function getPageUrlPlaceholders() {
    const urlInput = getPageUrlInput();

    if (urlInput) {
        return parsePageUrlPlaceholders(urlInput.value);
    }

    const scriptEl = document.querySelector('[data-page-entity-imports] script[data-page-url-placeholders]');

    if (! scriptEl?.textContent) {
        return [];
    }

    try {
        const parsed = JSON.parse(scriptEl.textContent.trim());

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function findPlugin(catalog, identifier) {
    return catalog.find((plugin) => String(plugin.identifier) === String(identifier)) ?? null;
}

function buildFunctionOptions(plugin, selectedFunction) {
    const functions = plugin?.functions ?? {};
    const modelFunctions = Object.entries(functions).filter(([, def]) => def?.builtin);
    const customFunctions = Object.entries(functions).filter(([, def]) => ! def?.builtin);

    let html = '';

    if (modelFunctions.length > 0) {
        html += '<optgroup label="Model functions">';
        html += modelFunctions.map(([key, definition]) => {
            const label = escapeHtml(definition.label ?? key);
            const selected = String(selectedFunction) === String(key) ? ' selected' : '';

            return `<option value="${escapeHtml(key)}"${selected}>${label}</option>`;
        }).join('');
        html += '</optgroup>';
    }

    if (customFunctions.length > 0) {
        html += '<optgroup label="Custom functions">';
        html += customFunctions.map(([key, definition]) => {
            const label = escapeHtml(definition.label ?? key);
            const selected = String(selectedFunction) === String(key) ? ' selected' : '';

            return `<option value="${escapeHtml(key)}"${selected}>${label}</option>`;
        }).join('');
        html += '</optgroup>';
    }

    return html;
}

function buildPathParamSelectHtml(baseName, paramName, paramValue, placeholders, disabled) {
    const disabledAttr = disabled ? ' disabled' : '';
    const selectedParam = String(paramValue.param ?? '');

    if (placeholders.length === 0) {
        return '<p class="text-muted small mb-0">Add <code>{name}</code> placeholders to the page URL above.</p>';
    }

    const options = placeholders.map((placeholder) => {
        const selected = selectedParam === placeholder ? ' selected' : '';

        return `<option value="${escapeHtml(placeholder)}"${selected}>${escapeHtml(placeholder)}</option>`;
    }).join('');

    const hasSelected = placeholders.includes(selectedParam);

    return `
        <select class="form-select form-select-sm"
                name="${escapeHtml(baseName)}[parameters][${escapeHtml(paramName)}][param]"
                data-entity-import-path-param${disabledAttr}>
            <option value="" disabled${hasSelected ? '' : ' selected'}>Select parameter…</option>
            ${options}
        </select>
    `;
}

function buildParameterField(baseName, parameter, value, disabled, placeholders) {
    const paramName = parameter.name;
    const paramLabel = escapeHtml(parameter.label ?? paramName);
    const isDynamic = Boolean(parameter.dynamic);
    const paramValue = value && typeof value === 'object' ? value : { mode: 'static', value: value ?? '' };
    let mode = String(paramValue.mode ?? 'static');

    if (mode === 'route_param') {
        mode = 'path_param';
    }

    const disabledAttr = disabled ? ' disabled' : '';
    const isPathMode = mode === 'path_param';
    const isQueryMode = mode === 'query_param';
    const isLegacySegment = mode === 'url_segment';

    if (! isDynamic) {
        return `
            <div class="col-12" data-entity-import-parameter="${escapeHtml(paramName)}">
                <label class="form-label form-label-sm">${paramLabel}</label>
                <input type="text"
                       class="form-control form-control-sm"
                       name="${escapeHtml(baseName)}[parameters][${escapeHtml(paramName)}][value]"
                       value="${escapeHtml(paramValue.value ?? '')}"${disabledAttr}>
                <input type="hidden" name="${escapeHtml(baseName)}[parameters][${escapeHtml(paramName)}][mode]" value="static">
            </div>
        `;
    }

    const legacySegmentOption = isLegacySegment
        ? `<option value="url_segment" selected>URL segment (legacy)</option>`
        : '';

    return `
        <div class="col-12" data-entity-import-parameter="${escapeHtml(paramName)}">
            <label class="form-label form-label-sm">${paramLabel}</label>
            <div class="row g-2">
                <div class="col-md-4">
                    <select class="form-select form-select-sm"
                            name="${escapeHtml(baseName)}[parameters][${escapeHtml(paramName)}][mode]"
                            data-entity-import-param-mode${disabledAttr}>
                        <option value="static"${mode === 'static' ? ' selected' : ''}>Static value</option>
                        <option value="path_param"${isPathMode ? ' selected' : ''}>Path parameter (/{name})</option>
                        <option value="query_param"${isQueryMode ? ' selected' : ''}>Query parameter (?name=)</option>
                        ${legacySegmentOption}
                    </select>
                </div>
                <div class="col-md-8">
                    <div data-entity-import-param-static${mode !== 'static' ? ' hidden' : ''}>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="${escapeHtml(baseName)}[parameters][${escapeHtml(paramName)}][value]"
                               value="${escapeHtml(paramValue.value ?? '')}"
                               placeholder="Static value"${disabledAttr}>
                    </div>
                    <div data-entity-import-param-path${isPathMode ? '' : ' hidden'}>
                        ${buildPathParamSelectHtml(baseName, paramName, paramValue, placeholders, disabled)}
                    </div>
                    <div data-entity-import-param-query${isQueryMode ? '' : ' hidden'}>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="${escapeHtml(baseName)}[parameters][${escapeHtml(paramName)}][param]"
                               value="${escapeHtml(paramValue.param ?? '')}"
                               placeholder="Query parameter name"${disabledAttr}>
                    </div>
                    <div data-entity-import-param-url-segment${isLegacySegment ? '' : ' hidden'}>
                        <input type="number"
                               class="form-control form-control-sm"
                               name="${escapeHtml(baseName)}[parameters][${escapeHtml(paramName)}][segment]"
                               value="${escapeHtml(paramValue.segment ?? 1)}"
                               min="1"
                               placeholder="Segment index"${disabledAttr}>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function buildParametersHtml(baseName, functionDefinition, values, disabled, placeholders) {
    const parameters = Array.isArray(functionDefinition?.parameters) ? functionDefinition.parameters : [];

    if (parameters.length === 0) {
        return '';
    }

    const fields = parameters.map((parameter) => {
        const paramName = parameter.name;
        const paramValue = values?.[paramName] ?? {};

        return buildParameterField(baseName, parameter, paramValue, disabled, placeholders);
    }).join('');

    return `
        <div class="small text-muted mb-2">Function parameters</div>
        <div class="row g-3">${fields}</div>
    `;
}

function bindParameterModeToggles(row, container) {
    const disabled = container.dataset.disabled === 'true';

    row.querySelectorAll('[data-entity-import-param-mode]').forEach((select) => {
        select.addEventListener('change', () => {
            const parameterContainer = select.closest('[data-entity-import-parameter]');

            if (! parameterContainer) {
                return;
            }

            const mode = select.value;
            const staticPanel = parameterContainer.querySelector('[data-entity-import-param-static]');
            const pathPanel = parameterContainer.querySelector('[data-entity-import-param-path]');
            const queryPanel = parameterContainer.querySelector('[data-entity-import-param-query]');
            const segmentPanel = parameterContainer.querySelector('[data-entity-import-param-url-segment]');

            if (staticPanel) {
                staticPanel.hidden = mode !== 'static';
            }

            if (pathPanel) {
                pathPanel.hidden = mode !== 'path_param';

                if (mode === 'path_param') {
                    refreshPathParamPanel(pathPanel, select, disabled);
                }
            }

            if (queryPanel) {
                queryPanel.hidden = mode !== 'query_param';
            }

            if (segmentPanel) {
                segmentPanel.hidden = mode !== 'url_segment';
            }
        });
    });
}

function refreshPathParamPanel(pathPanel, modeSelect, disabled) {
    const modeName = modeSelect.getAttribute('name') ?? '';
    const match = modeName.match(/^(.*)\[parameters\]\[([^\]]+)\]\[mode\]$/);

    if (! match) {
        return;
    }

    const [, baseName, paramName] = match;
    const select = pathPanel.querySelector('[data-entity-import-path-param]');
    const selected = select?.value ?? '';
    const placeholders = getPageUrlPlaceholders();

    pathPanel.innerHTML = buildPathParamSelectHtml(
        baseName,
        paramName,
        { param: selected },
        placeholders,
        disabled
    );
}

function refreshPathParamDropdowns(container) {
    const placeholders = getPageUrlPlaceholders();
    const disabled = container.dataset.disabled === 'true';

    container.querySelectorAll('[data-entity-import-param-mode]').forEach((modeSelect) => {
        if (modeSelect.value !== 'path_param') {
            return;
        }

        const parameterContainer = modeSelect.closest('[data-entity-import-parameter]');
        const pathPanel = parameterContainer?.querySelector('[data-entity-import-param-path]');

        if (! pathPanel) {
            return;
        }

        refreshPathParamPanel(pathPanel, modeSelect, disabled);
    });

    const scriptEl = container.querySelector('script[data-page-url-placeholders]');

    if (scriptEl) {
        scriptEl.textContent = JSON.stringify(placeholders);
    }
}

function readRowValues(row) {
    const baseName = row.querySelector('[data-entity-import-variable]')?.getAttribute('name')?.replace(/\[variable\]$/, '') ?? '';
    const values = {
        variable: row.querySelector('[data-entity-import-variable]')?.value ?? '',
        plugin: row.querySelector('[data-entity-import-plugin]')?.value ?? '',
        function: row.querySelector('[data-entity-import-function]')?.value ?? '',
        parameters: {},
    };

    if (baseName === '') {
        return values;
    }

    row.querySelectorAll(`[name^="${baseName}[parameters]"]`).forEach((input) => {
        const match = input.getAttribute('name')?.match(/\[parameters\]\[([^\]]+)\]\[([^\]]+)\]$/);

        if (! match) {
            return;
        }

        const [, paramName, part] = match;

        if (! values.parameters[paramName]) {
            values.parameters[paramName] = {};
        }

        values.parameters[paramName][part] = input.value;
    });

    return values;
}

function refreshFunctionSelect(row, catalog, disabled, placeholders) {
    const pluginSelect = row.querySelector('[data-entity-import-plugin]');
    const functionSelect = row.querySelector('[data-entity-import-function]');
    const parametersContainer = row.querySelector('[data-entity-import-parameters]');
    const plugin = findPlugin(catalog, pluginSelect?.value ?? '');
    const currentFunction = functionSelect?.value ?? '';
    const rowValues = readRowValues(row);
    const baseName = pluginSelect?.getAttribute('name')?.replace(/\[plugin\]$/, '') ?? '';

    if (! functionSelect) {
        return;
    }

    const placeholderSelected = currentFunction === '' ? ' selected' : '';

    functionSelect.innerHTML = `<option value="" disabled${placeholderSelected}>Select a function…</option>`
        + buildFunctionOptions(plugin, currentFunction);
    functionSelect.disabled = disabled || ! plugin;

    if (currentFunction !== '') {
        functionSelect.value = currentFunction;
    }

    const selectedFunction = plugin?.functions?.[functionSelect.value] ?? null;

    if (parametersContainer) {
        const container = row.closest('[data-page-entity-imports]');

        parametersContainer.innerHTML = buildParametersHtml(
            baseName,
            selectedFunction,
            rowValues.parameters,
            disabled,
            placeholders ?? getPageUrlPlaceholders()
        );
        bindParameterModeToggles(row, container ?? document.createElement('div'));
    }
}

function reindexRows(repeater) {
    const name = repeater.dataset.name ?? 'entity_imports';

    repeater.querySelectorAll('[data-entity-import-row]').forEach((row, index) => {
        row.dataset.index = String(index);
        row.querySelectorAll('[name]').forEach((input) => {
            const currentName = input.getAttribute('name');

            if (! currentName) {
                return;
            }

            input.setAttribute('name', currentName.replace(new RegExp(`^${name}\\[\\d+\\]`), `${name}[${index}]`));
        });
    });
}

function dispatchImportsChanged(container) {
    container.dispatchEvent(new CustomEvent('loom:entity-imports-changed', { bubbles: true }));
}

function bindRow(row, container, catalog, disabled) {
    const pluginSelect = row.querySelector('[data-entity-import-plugin]');
    const functionSelect = row.querySelector('[data-entity-import-function]');
    const variableInput = row.querySelector('[data-entity-import-variable]');

    pluginSelect?.addEventListener('change', () => {
        refreshFunctionSelect(row, catalog, disabled, getPageUrlPlaceholders());
        dispatchImportsChanged(container);
    });

    functionSelect?.addEventListener('change', () => {
        refreshFunctionSelect(row, catalog, disabled, getPageUrlPlaceholders());
        dispatchImportsChanged(container);
    });

    variableInput?.addEventListener('input', () => dispatchImportsChanged(container));

    row.querySelector('[data-entity-imports-remove]')?.addEventListener('click', () => {
        row.remove();
        const repeater = container.querySelector('[data-entity-imports-repeater]');
        const items = repeater?.querySelector('[data-entity-imports-items]');

        if (items && items.querySelectorAll('[data-entity-import-row]').length === 0) {
            items.hidden = true;
        }

        if (repeater) {
            reindexRows(repeater);
        }

        dispatchImportsChanged(container);
    });

    bindParameterModeToggles(row, container);
}

export function readEntityImportsFromForm(container = document) {
    const root = container.querySelector('[data-page-entity-imports]') ?? container.closest('[data-page-entity-imports]');

    if (! root) {
        return [];
    }

    const imports = [];

    root.querySelectorAll('[data-entity-import-row]').forEach((row) => {
        const values = readRowValues(row);

        if (values.variable.trim() !== '') {
            imports.push(values);
        }
    });

    return imports;
}

export function initPageEntityImports() {
    const container = document.querySelector('[data-page-entity-imports]');

    if (! container || container.dataset.initialized === 'true') {
        return;
    }

    container.dataset.initialized = 'true';

    const catalog = parseCatalog(container);
    const disabled = container.dataset.disabled === 'true';
    const repeater = container.querySelector('[data-entity-imports-repeater]');
    const items = repeater?.querySelector('[data-entity-imports-items]');
    const prototypeTemplate = repeater?.querySelector('template[data-entity-imports-prototype]');

    container.querySelectorAll('[data-entity-import-row]').forEach((row) => {
        bindRow(row, container, catalog, disabled);
    });

    repeater?.querySelector('[data-entity-imports-add]')?.addEventListener('click', () => {
        if (! prototypeTemplate || ! items) {
            return;
        }

        const index = items.querySelectorAll('[data-entity-import-row]').length;
        const html = prototypeTemplate.innerHTML.replaceAll('__INDEX__', String(index));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;

        if (! row) {
            return;
        }

        items.appendChild(row);
        items.hidden = false;
        bindRow(row, container, catalog, disabled);
        refreshPathParamDropdowns(container);
        dispatchImportsChanged(container);
    });

    const urlInput = getPageUrlInput();

    urlInput?.addEventListener('input', () => {
        refreshPathParamDropdowns(container);
        container.dispatchEvent(new CustomEvent('loom:page-url-changed', { bubbles: true }));
    });

    refreshPathParamDropdowns(container);
}
