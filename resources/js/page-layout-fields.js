import {
    buildScalarParameterField,
    resolveEffectiveParameterType,
} from './dynamic-parameter-fields';
import { destroyRichTextEditors, initRichTextEditors } from './rich-text-editor';
import { initCodeEditors } from './code-editor';
import { initDynamicCodeEditors } from './dynamic-code-editor';
import { initMediaFinders } from './media-finder';
import { initUrlParameters } from './url-parameter';
import { readEntityImportsFromForm } from './page-entity-imports';

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function parseJsonScript(root, selector) {
    const scriptEl = root.querySelector(selector);

    if (! scriptEl?.textContent) {
        return null;
    }

    try {
        return JSON.parse(scriptEl.textContent.trim());
    } catch {
        return null;
    }
}

function findLayout(catalog, layoutSlug) {
    return catalog.find((layout) => String(layout.slug) === String(layoutSlug)) ?? null;
}

function findPlugin(catalog, identifier) {
    return catalog.find((plugin) => String(plugin.identifier) === String(identifier)) ?? null;
}

function isDynamicValue(value) {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) {
        return false;
    }

    if (value._mode === 'dynamic') {
        return true;
    }

    if (value._mode === 'static') {
        return false;
    }

    if (typeof value.import === 'string' && value.import.trim() !== '') {
        return true;
    }

    return typeof value.dynamic === 'string' && value.dynamic.trim() !== '';
}

function resolveFieldValue(values, parameterName) {
    if (! values || typeof values !== 'object') {
        return '';
    }

    const value = values[parameterName];

    if (isDynamicValue(value)) {
        return value;
    }

    if (value !== undefined && value !== null && typeof value === 'object' && ! Array.isArray(value)) {
        if (Object.prototype.hasOwnProperty.call(value, 'static')) {
            return value.static ?? '';
        }
    }

    if (value !== undefined && value !== null) {
        return value;
    }

    return '';
}

function syncLayoutFieldRowState(row, disabled) {
    const mode = row.querySelector('[data-layout-field-mode]')?.value ?? 'static';
    const importSelect = row.querySelector('[data-layout-field-import]');
    const fieldSelect = row.querySelector('[data-layout-field-return]');

    if (importSelect) {
        importSelect.disabled = disabled || mode !== 'dynamic';
    }

    if (fieldSelect) {
        const hasImport = (importSelect?.value ?? '').trim() !== '';
        fieldSelect.disabled = disabled || mode !== 'dynamic' || ! hasImport;
    }
}

function enableLayoutFieldsForSubmit(container) {
    container?.querySelectorAll('input, select, textarea').forEach((element) => {
        element.disabled = false;
    });
}

export { enableLayoutFieldsForSubmit };

function slugify(value) {
    return String(value).replace(/[^a-zA-Z0-9_-]/g, '-');
}

function splitDynamicPath(path) {
    const parts = String(path).split('.', 2);

    return {
        import: parts[0] ?? '',
        field: parts[1] ?? '',
    };
}

function resolveReturnFields(entityImports, pluginsCatalog, importVariable) {
    const selectedImport = entityImports.find((item) => String(item.variable) === String(importVariable));

    if (! selectedImport) {
        return [];
    }

    const plugin = findPlugin(pluginsCatalog, selectedImport.plugin);
    const functionDefinition = plugin?.functions?.[selectedImport.function];

    return Array.isArray(functionDefinition?.returns) ? functionDefinition.returns : [];
}

function buildStaticInput(baseName, parameter, value, disabled) {
    const type = resolveEffectiveParameterType(parameter, value);

    return buildScalarParameterField({
        fieldName: `${baseName}[${parameter.name}][static]`,
        label: null,
        type,
        value: isDynamicValue(value) ? '' : value,
        defaultValue: parameter.default ?? '',
        tip: parameter.tip,
        required: parameter.required,
        disabled,
        options: parameter.options ?? [],
        colClass: 'mb-0',
        controlClass: 'form-control form-control-sm',
    });
}

function buildImportOptions(entityImports, selectedImport) {
    return entityImports.map((item) => {
        const variable = String(item.variable ?? '').trim();

        if (variable === '') {
            return '';
        }

        const selected = String(selectedImport) === variable ? ' selected' : '';

        return `<option value="${escapeHtml(variable)}"${selected}>$${escapeHtml(variable)}</option>`;
    }).join('');
}

function buildReturnFieldOptions(returnFields, selectedField) {
    return returnFields.map((returnField) => {
        const name = String(returnField.name ?? '').trim();

        if (name === '') {
            return '';
        }

        const label = escapeHtml(returnField.label ?? name);
        const selected = String(selectedField) === name ? ' selected' : '';

        return `<option value="${escapeHtml(name)}"${selected}>${label}</option>`;
    }).join('');
}

function buildFieldRow(baseName, parameter, value, disabled, entityImports, pluginsCatalog) {
    const isDynamic = isDynamicValue(value);
    const mode = isDynamic ? 'dynamic' : 'static';
    const paramLabel = escapeHtml(parameter.label ?? parameter.name);
    const paramName = escapeHtml(parameter.name);
    const paramTip = String(parameter.tip ?? '').trim();
    const tipHtml = paramTip ? `<div class="form-text mb-0">${escapeHtml(paramTip)}</div>` : '';
    const staticHtml = buildStaticInput(baseName, parameter, value, disabled);
    const dynamicImport = isDynamic
        ? (value.import ?? splitDynamicPath(value.dynamic ?? '').import)
        : '';
    const dynamicField = isDynamic
        ? (value.field ?? splitDynamicPath(value.dynamic ?? '').field)
        : '';
    const fieldId = slugify(`${baseName}-${parameter.name}`);
    const hasEntityImports = entityImports.length > 0;
    const returnFields = resolveReturnFields(entityImports, pluginsCatalog, dynamicImport);
    const dynamicDisabled = disabled || ! hasEntityImports ? ' disabled' : '';

    return `
        <tr class="loom-layout-field" data-layout-field="${paramName}">
            <td>
                <div class="fw-medium">${paramLabel}</div>
                ${tipHtml}
                <div class="text-muted small">${paramName}</div>
            </td>
            <td>
                <select class="form-select form-select-sm loom-layout-field-mode"
                        name="${escapeHtml(baseName)}[${paramName}][_mode]"
                        data-layout-field-mode
                        ${disabled ? 'disabled' : ''}>
                    <option value="static"${mode === 'static' ? ' selected' : ''}>Static</option>
                    <option value="dynamic"${mode === 'dynamic' ? ' selected' : ''}${hasEntityImports ? '' : ' disabled'}>Dynamic</option>
                </select>
            </td>
            <td>
                <div class="loom-layout-field-static" data-layout-field-static${mode === 'dynamic' ? ' hidden' : ''}>
                    ${staticHtml}
                </div>
                <div class="loom-layout-field-dynamic" data-layout-field-dynamic${mode === 'static' ? ' hidden' : ''}>
                    ${hasEntityImports ? '' : '<div class="alert alert-info py-2 small mb-2">Add at least one entity import before using dynamic layout fields.</div>'}
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm mb-1" for="${fieldId}-import">Import</label>
                            <select class="form-select form-select-sm"
                                    id="${fieldId}-import"
                                    name="${escapeHtml(baseName)}[${paramName}][import]"
                                    data-layout-field-import${dynamicDisabled}>
                                <option value="" disabled${dynamicImport === '' ? ' selected' : ''}>Select import…</option>
                                ${buildImportOptions(entityImports, dynamicImport)}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm mb-1" for="${fieldId}-field">Field</label>
                            <select class="form-select form-select-sm"
                                    id="${fieldId}-field"
                                    name="${escapeHtml(baseName)}[${paramName}][field]"
                                    data-layout-field-return
                                    ${disabled ? 'disabled' : ''}>
                                <option value="" disabled${dynamicField === '' ? ' selected' : ''}>Select field…</option>
                                ${buildReturnFieldOptions(returnFields, dynamicField)}
                            </select>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

function buildSegmentTable(baseName, parameters, values, disabled, entityImports, pluginsCatalog) {
    if (! Array.isArray(parameters) || parameters.length === 0) {
        return '<p class="text-muted small mb-0">This segment has no configurable fields.</p>';
    }

    const rows = parameters.map((parameter) => {
        return buildFieldRow(
            baseName,
            parameter,
            resolveFieldValue(values, parameter.name),
            disabled,
            entityImports,
            pluginsCatalog
        );
    }).join('');

    return `
        <div class="table-responsive">
            <table class="table table-sm align-middle loom-layout-fields-table mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="loom-layout-fields-table__field-col">Field</th>
                        <th scope="col" class="loom-layout-fields-table__mode-col">Mode</th>
                        <th scope="col">Value</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function bindModeToggles(container, disabled) {
    container.querySelectorAll('[data-layout-field-mode]').forEach((select) => {
        select.addEventListener('change', () => {
            const row = select.closest('.loom-layout-field');

            if (! row) {
                return;
            }

            const mode = select.value;
            const staticPanel = row.querySelector('[data-layout-field-static]');
            const dynamicPanel = row.querySelector('[data-layout-field-dynamic]');

            if (staticPanel) {
                staticPanel.hidden = mode !== 'static';
            }

            if (dynamicPanel) {
                dynamicPanel.hidden = mode !== 'dynamic';
            }

            syncLayoutFieldRowState(row, disabled);
        });
    });

    container.querySelectorAll('.loom-layout-field').forEach((row) => {
        syncLayoutFieldRowState(row, disabled);
    });
}

function bindImportFieldToggles(container, entityImports, pluginsCatalog, disabled) {
    container.querySelectorAll('[data-layout-field-import]').forEach((select) => {
        select.addEventListener('change', () => {
            const row = select.closest('.loom-layout-field');
            const fieldSelect = row?.querySelector('[data-layout-field-return]');

            if (! fieldSelect) {
                return;
            }

            const returnFields = resolveReturnFields(entityImports, pluginsCatalog, select.value);
            const currentValue = fieldSelect.value;
            fieldSelect.innerHTML = '<option value="" disabled>Select field…</option>'
                + buildReturnFieldOptions(returnFields, currentValue);

            if (row) {
                syncLayoutFieldRowState(row, disabled);
            }
        });
    });
}

function renderLayoutFields(container, layout, values, disabled, entityImports, pluginsCatalog) {
    const contentEl = container.querySelector('[data-page-layout-fields-content]');
    const baseName = container.dataset.name ?? 'layout_fields';
    const fieldId = container.id ?? 'field-layout-fields';

    destroyRichTextEditors(contentEl);
    contentEl.innerHTML = '';

    if (! layout || ! Array.isArray(layout.segments) || layout.segments.length === 0) {
        contentEl.innerHTML = '<p class="text-muted small mb-0">This layout has no configurable segment fields.</p>';

        return;
    }

    const tabsId = `${fieldId}-segment-tabs`;
    const tabButtons = layout.segments.map((segment, index) => {
        const segmentPath = segment.path ?? '';
        const segmentName = escapeHtml(segment.name ?? segmentPath);
        const tabId = `${fieldId}-tab-${slugify(segmentPath)}`;
        const paneId = `${fieldId}-pane-${slugify(segmentPath)}`;

        return `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${index === 0 ? 'active' : ''}"
                        id="${tabId}"
                        data-bs-toggle="tab"
                        data-bs-target="#${paneId}"
                        type="button"
                        role="tab"
                        aria-controls="${paneId}"
                        aria-selected="${index === 0 ? 'true' : 'false'}">
                    ${segmentName}
                </button>
            </li>
        `;
    }).join('');

    const tabPanes = layout.segments.map((segment, index) => {
        const segmentPath = segment.path ?? '';
        const parameters = Array.isArray(segment.parameters) ? segment.parameters : [];
        const segmentValues = values?.[segmentPath] ?? {};
        const segmentBase = `${baseName}[${segmentPath}]`;
        const paneId = `${fieldId}-pane-${slugify(segmentPath)}`;
        const tabId = `${fieldId}-tab-${slugify(segmentPath)}`;

        return `
            <div class="tab-pane fade ${index === 0 ? 'show active' : ''}"
                 id="${paneId}"
                 role="tabpanel"
                 aria-labelledby="${tabId}"
                 data-layout-segment="${escapeHtml(segmentPath)}">
                ${buildSegmentTable(segmentBase, parameters, segmentValues, disabled, entityImports, pluginsCatalog)}
            </div>
        `;
    }).join('');

    contentEl.innerHTML = `
        <ul class="nav nav-tabs loom-layout-fields-tabs" id="${tabsId}" role="tablist">${tabButtons}</ul>
        <div class="tab-content loom-layout-fields-tab-content border border-top-0 rounded-bottom p-3">${tabPanes}</div>
    `;

    bindModeToggles(contentEl, disabled);
    bindImportFieldToggles(contentEl, entityImports, pluginsCatalog, disabled);
    initCodeEditors(contentEl);
    initDynamicCodeEditors(contentEl);
    initRichTextEditors(contentEl);
    initMediaFinders(contentEl);
    initUrlParameters(contentEl);
}

function readLayoutSelect() {
    return document.getElementById('field-layout');
}

function readValuesFromForm(container, contentEl) {
    const values = {};
    const baseName = container.dataset.name ?? 'layout_fields';
    const escapedBase = baseName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    contentEl?.querySelectorAll(`[name^="${baseName}["]`).forEach((input) => {
        const name = input.getAttribute('name');

        if (! name) {
            return;
        }

        const match = name.match(new RegExp(`^${escapedBase}\\[([^\\]]+)\\]\\[([^\\]]+)\\](?:\\[(static|import|field|_mode)\\])?$`));

        if (! match) {
            return;
        }

        const [, segmentPath, fieldName, part] = match;

        if (! values[segmentPath]) {
            values[segmentPath] = {};
        }

        if (part === '_mode') {
            values[segmentPath][fieldName] = values[segmentPath][fieldName] ?? {};
            values[segmentPath][fieldName]._mode = input.value;

            return;
        }

        if (part === 'import') {
            values[segmentPath][fieldName] = values[segmentPath][fieldName] ?? {};
            values[segmentPath][fieldName].import = input.value;

            return;
        }

        if (part === 'field') {
            values[segmentPath][fieldName] = values[segmentPath][fieldName] ?? {};
            values[segmentPath][fieldName].field = input.value;

            return;
        }

        if (part === 'static') {
            values[segmentPath][fieldName] = input.type === 'checkbox'
                ? (input.checked ? input.value : '0')
                : input.value;
        }
    });

    return values;
}

export function initPageLayoutFields() {
    const container = document.querySelector('[data-page-layout-fields]');

    if (! container || container.dataset.initialized === 'true') {
        return;
    }

    container.dataset.initialized = 'true';

    const catalog = parseJsonScript(container, 'script[data-layouts-catalog]') ?? [];
    const pluginsCatalog = parseJsonScript(container, 'script[data-plugins-functions-catalog]') ?? [];
    let currentValues = parseJsonScript(container, 'script[data-layout-fields-values]') ?? {};
    const disabled = container.dataset.disabled === 'true';
    const layoutSelect = readLayoutSelect();
    const contentEl = container.querySelector('[data-page-layout-fields-content]');

    function currentEntityImports() {
        const fromForm = readEntityImportsFromForm(document);

        if (fromForm.length > 0) {
            return fromForm;
        }

        return parseJsonScript(container, 'script[data-entity-imports-for-layout]') ?? [];
    }

    function refresh() {
        const layoutSlug = layoutSelect?.value ?? '';
        const layout = findLayout(catalog, layoutSlug);

        currentValues = readValuesFromForm(container, contentEl);
        renderLayoutFields(container, layout, currentValues, disabled, currentEntityImports(), pluginsCatalog);
    }

    const hasServerRenderedFields = contentEl?.querySelector('[data-layout-segment]') !== null;

    if (hasServerRenderedFields) {
        bindModeToggles(contentEl, disabled);
        bindImportFieldToggles(contentEl, currentEntityImports(), pluginsCatalog, disabled);
        initCodeEditors(contentEl);
        initDynamicCodeEditors(contentEl);
        initRichTextEditors(contentEl);
        initMediaFinders(contentEl);
        initUrlParameters(contentEl);

        container.querySelector('[data-layout-fields-errors]')?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
    } else {
        refresh();
    }

    layoutSelect?.addEventListener('change', refresh);
    document.addEventListener('loom:entity-imports-changed', refresh);

    document.querySelector('[data-loom-form="pages-basic"]')?.addEventListener('submit', () => {
        enableLayoutFieldsForSubmit(container);
    }, { capture: true });
}
