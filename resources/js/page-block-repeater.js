import {
    buildScalarParameterField,
    groupByRow,
    resolveEffectiveParameterType,
    resolveParameterColClass,
    resolveParameterRow,
    richTextFieldId,
} from './dynamic-parameter-fields';
import { destroyRichTextEditors, initRichTextEditors, syncRichTextEditors } from './rich-text-editor';
import { initCodeEditors } from './code-editor';
import { initDynamicCodeEditors } from './dynamic-code-editor';
import { initMediaFinders } from './media-finder';
import { initUrlParameters } from './url-parameter';

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function parseCatalog(raw) {
    if (!raw) {
        return [];
    }

    try {
        const parsed = JSON.parse(raw);

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function readBlocksCatalog(repeater) {
    const scriptEl = repeater.querySelector('script[data-blocks-catalog]');

    if (scriptEl?.textContent) {
        const fromScript = parseCatalog(scriptEl.textContent.trim());

        if (fromScript.length > 0) {
            return fromScript;
        }
    }

    return parseCatalog(repeater.dataset.blocksCatalog);
}

function findBlock(catalog, blockSlug) {
    return catalog.find((block) => String(block.slug) === String(blockSlug)) ?? null;
}

function resolveParameterValue(values, parameter) {
    const name = parameter.name;

    if (Object.prototype.hasOwnProperty.call(values, name)) {
        return values[name];
    }

    return parameter.default ?? '';
}

function defaultValuesForBlock(block) {
    const defaults = {};

    if (! block || ! Array.isArray(block.parameters)) {
        return defaults;
    }

    block.parameters.forEach((parameter) => {
        if (parameter?.name && parameter.default !== undefined) {
            defaults[parameter.name] = parameter.default;
        }
    });

    return defaults;
}

function updateParameterBaseNames(parametersEl, oldBase, newBase) {
    if (oldBase === newBase) {
        return;
    }

    syncRichTextEditors(parametersEl);

    parametersEl.querySelectorAll('[name]').forEach((input) => {
        const name = input.getAttribute('name');

        if (! name || ! name.startsWith(`${oldBase}[`)) {
            return;
        }

        input.name = name.replace(oldBase, newBase);
    });

    parametersEl.querySelectorAll('[data-rich-text-editor]').forEach((mount) => {
        const textarea = mount.dataset.target ? document.getElementById(mount.dataset.target) : null;

        if (! textarea?.name) {
            return;
        }

        const newId = richTextFieldId(textarea.name);
        textarea.id = newId;
        mount.dataset.target = newId;
    });
}

function hasRenderedParameters(parametersEl) {
    return parametersEl.querySelector('.loom-form-row, [data-value-repeater]') !== null;
}

function parameterTipHtml(parameter) {
    const tip = String(parameter.tip ?? '').trim();

    if (! tip) {
        return '';
    }

    return `<div class="form-text">${escapeHtml(tip)}</div>`;
}

function readInputValue(input) {
    if (input.type === 'checkbox') {
        return input.checked ? input.value : '0';
    }

    return input.value;
}

function collectParameterValues(parametersEl, baseName) {
    const values = {};
    const valuesPrefix = `${baseName}[values]`;

    parametersEl.querySelectorAll('[name]').forEach((input) => {
        const name = input.getAttribute('name');

        if (! name || ! name.startsWith(valuesPrefix)) {
            return;
        }

        const rest = name.slice(valuesPrefix.length);
        const compoundMatch = rest.match(/^\[([^\]]+)\]\[(url|alt|class|id|target|file)\]$/);

        if (compoundMatch) {
            const [, paramName, subKey] = compoundMatch;

            if (! values[paramName] || typeof values[paramName] !== 'object' || Array.isArray(values[paramName])) {
                values[paramName] = {};
            }

            if (subKey === 'file') {
                return;
            }

            values[paramName][subKey] = readInputValue(input);

            return;
        }

        const scalarMatch = rest.match(/^\[([^\]]+)\]$/);

        if (scalarMatch) {
            values[scalarMatch[1]] = readInputValue(input);

            return;
        }

        const repeaterMatch = rest.match(/^\[([^\]]+)\]\[(\d+)\]\[([^\]]+)\]$/);

        if (repeaterMatch) {
            const [, repeaterName, rowIndex, fieldName] = repeaterMatch;

            if (! values[repeaterName]) {
                values[repeaterName] = [];
            }

            if (! values[repeaterName][rowIndex]) {
                values[repeaterName][rowIndex] = {};
            }

            values[repeaterName][rowIndex][fieldName] = readInputValue(input);
        }
    });

    Object.keys(values).forEach((key) => {
        if (Array.isArray(values[key])) {
            values[key] = values[key].filter((row) => row && typeof row === 'object');
        }
    });

    return values;
}

function buildSubFieldInput(baseName, repeaterName, rowIndex, field, value, disabled) {
    const type = resolveEffectiveParameterType(field, value);

    return buildScalarParameterField({
        fieldName: `${baseName}[values][${repeaterName}][${rowIndex}][${field.name}]`,
        label: field.label ?? field.name,
        type,
        value,
        defaultValue: field.default ?? '',
        tip: field.tip,
        required: field.required,
        disabled,
        options: field.options ?? [],
        colClass: resolveParameterColClass(field, value),
        controlClass: 'form-control form-control-sm',
    });
}

function buildRepeaterFieldsHtml(baseName, parameter, rowIndex, rowValues, disabled) {
    const fields = Array.isArray(parameter.fields) ? parameter.fields : [];
    const rowGroups = groupByRow(fields, resolveParameterRow);

    return rowGroups.map(([, rowFields]) => {
        const fieldsHtml = rowFields.map((field) => {
            return buildSubFieldInput(
                baseName,
                parameter.name,
                rowIndex,
                field,
                rowValues?.[field.name] ?? '',
                disabled
            );
        }).join('');

        return `<div class="row g-2">${fieldsHtml}</div>`;
    }).join('');
}

function buildRepeaterRowHtml(baseName, parameter, rowIndex, rowValues, disabled) {
    const itemLabel = parameter.item ? `${parameter.item}` : 'Item';
    const fieldsHtml = buildRepeaterFieldsHtml(baseName, parameter, rowIndex, rowValues, disabled);

    return `
        <div class="loom-value-repeater__item" data-value-repeater-item data-index="${rowIndex}">
            <div class="loom-value-repeater__item-header">
                <span class="loom-value-repeater__item-label">${escapeHtml(itemLabel)} ${rowIndex + 1}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" data-value-repeater-remove${disabled ? ' disabled' : ''}>Remove</button>
            </div>
            ${fieldsHtml}
        </div>
    `;
}

function bindValueRepeater(container, baseName, parameter, initialRows, disabled) {
    const repeater = container.querySelector('[data-value-repeater]');

    if (! repeater || repeater.dataset.bound === 'true') {
        return;
    }

    repeater.dataset.bound = 'true';

    const itemsEl = repeater.querySelector('[data-value-repeater-items]');
    const addBtn = repeater.querySelector('[data-value-repeater-add]');
    const rows = Array.isArray(initialRows) ? initialRows : [];

    function renderRows(rowData) {
        itemsEl.innerHTML = rowData.map((row, index) => {
            return buildRepeaterRowHtml(baseName, parameter, index, row, disabled);
        }).join('');

        itemsEl.hidden = rowData.length === 0;
        initRichTextEditors(itemsEl);
        initMediaFinders(itemsEl);
        initUrlParameters(itemsEl);
    }

    function getCurrentRows() {
        const rowMap = {};
        const prefix = `${baseName}[values][${parameter.name}]`;

        itemsEl.querySelectorAll('[name]').forEach((input) => {
            const name = input.getAttribute('name');

            if (! name || ! name.startsWith(prefix)) {
                return;
            }

            const match = name.slice(prefix.length).match(/^\[(\d+)\]\[([^\]]+)\]$/);

            if (! match) {
                return;
            }

            const rowIndex = match[1];
            const fieldName = match[2];

            if (! rowMap[rowIndex]) {
                rowMap[rowIndex] = {};
            }

            rowMap[rowIndex][fieldName] = readInputValue(input);
        });

        return Object.keys(rowMap)
            .sort((a, b) => Number(a) - Number(b))
            .map((key) => rowMap[key]);
    }

    renderRows(rows);

    addBtn?.addEventListener('click', () => {
        if (disabled) {
            return;
        }

        const currentRows = getCurrentRows();
        currentRows.push({});
        renderRows(currentRows);
    });

    repeater.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('[data-value-repeater-remove]');

        if (! removeBtn || disabled) {
            return;
        }

        const item = removeBtn.closest('[data-value-repeater-item]');

        if (! item) {
            return;
        }

        item.remove();

        const currentRows = getCurrentRows();
        renderRows(currentRows);
    });
}

function buildRepeaterInput(baseName, parameter, value, disabled) {
    const label = escapeHtml(parameter.label ?? parameter.name);
    const tipHtml = parameterTipHtml(parameter);
    const rows = Array.isArray(value) ? value : [];
    const repeaterId = `repeater-${baseName.replace(/[^a-zA-Z0-9_-]/g, '-')}-${parameter.name}`;
    const colClass = resolveParameterColClass(parameter);

    return `
        <div class="${colClass}" data-value-repeater-wrap="${escapeHtml(parameter.name)}">
            <label class="form-label">${label}</label>
            ${tipHtml}
            <div class="loom-value-repeater" id="${repeaterId}" data-value-repeater data-parameter-name="${escapeHtml(parameter.name)}">
                <div class="loom-value-repeater__items" data-value-repeater-items${rows.length === 0 ? ' hidden' : ''}></div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-value-repeater-add${disabled ? ' disabled' : ''}>Add ${escapeHtml(parameter.item || 'item')}</button>
            </div>
        </div>
    `;
}

function buildParameterInput(name, parameter, value, disabled) {
    if ((parameter.type ?? 'text') === 'repeater') {
        return buildRepeaterInput(name, parameter, value, disabled);
    }

    const type = resolveEffectiveParameterType(parameter, value);

    return buildScalarParameterField({
        fieldName: `${name}[values][${parameter.name}]`,
        label: parameter.label ?? parameter.name,
        type,
        value,
        defaultValue: parameter.default ?? '',
        tip: parameter.tip,
        required: parameter.required,
        disabled,
        options: parameter.options ?? [],
        colClass: resolveParameterColClass(parameter, value),
    });
}

function renderParameters(container, catalog, blockSlug, baseName, initialValues, disabled) {
    const block = findBlock(catalog, blockSlug);

    destroyRichTextEditors(container);
    container.innerHTML = '';

    if (!block || !Array.isArray(block.parameters) || block.parameters.length === 0) {
        container.innerHTML = blockSlug
            ? '<div class="col-12"><p class="text-muted small mb-0">This block has no dynamic parameters.</p></div>'
            : '';

        return;
    }

    const values = initialValues && typeof initialValues === 'object' ? initialValues : {};
    const rowGroups = groupByRow(block.parameters, resolveParameterRow);

    rowGroups.forEach(([, rowParameters]) => {
        const rowEl = document.createElement('div');
        rowEl.className = 'loom-form-row row g-3 mb-3';
        container.appendChild(rowEl);

        rowParameters.forEach((parameter) => {
            rowEl.insertAdjacentHTML(
                'beforeend',
                buildParameterInput(baseName, parameter, resolveParameterValue(values, parameter), disabled)
            );

            if (parameter.type === 'repeater') {
                const wrap = rowEl.querySelector(`[data-value-repeater-wrap="${parameter.name}"]`);

                if (wrap) {
                    bindValueRepeater(
                        wrap,
                        baseName,
                        parameter,
                        Array.isArray(values[parameter.name]) ? values[parameter.name] : [],
                        disabled
                    );
                }
            }
        });
    });

    initCodeEditors(container);
    initDynamicCodeEditors(container);
    initRichTextEditors(container);
    initMediaFinders(container);
    initUrlParameters(container);
}

function getRowBaseName(repeater, rowIndex) {
    const baseName = repeater.dataset.name;

    return `${baseName}[${rowIndex}]`;
}

function updateBlockAccordionIds(item, repeaterId, index) {
    const collapseId = `${repeaterId}-block-${index}-body`;
    const collapseEl = item.querySelector('[data-block-repeater-collapse]');
    const toggleBtn = item.querySelector('[data-block-repeater-toggle]');

    if (! collapseEl || ! toggleBtn) {
        return;
    }

    if (collapseEl.id === collapseId) {
        return;
    }

    collapseEl.id = collapseId;
    toggleBtn.setAttribute('data-bs-target', `#${collapseId}`);
    toggleBtn.setAttribute('aria-controls', collapseId);
}

function reindexBlockRepeater(repeater) {
    const itemsEl = repeater.querySelector('[data-block-repeater-items]');
    const items = itemsEl.querySelectorAll(':scope > [data-block-repeater-item]');
    const repeaterId = repeater.id || 'block-repeater';

    items.forEach((item, index) => {
        const previousBaseName = item.dataset.baseName ?? getRowBaseName(repeater, item.dataset.index);
        const baseName = getRowBaseName(repeater, index);

        item.dataset.index = String(index);
        item.dataset.baseName = baseName;

        updateBlockAccordionIds(item, repeaterId, index);

        const select = item.querySelector('[data-block-repeater-select]');
        const parametersEl = item.querySelector('[data-block-repeater-parameters]');

        if (select) {
            select.name = `${baseName}[block_slug]`;
        }

        if (! select || ! parametersEl || ! hasRenderedParameters(parametersEl)) {
            return;
        }

        if (previousBaseName !== baseName) {
            updateParameterBaseNames(parametersEl, previousBaseName, baseName);
        }
    });

    if (items.length === 0) {
        itemsEl.hidden = true;
    } else {
        itemsEl.hidden = false;
    }
}

function bindBlockRepeaterRow(repeater, item) {
    const catalog = readBlocksCatalog(repeater);
    const isDisabled = repeater.dataset.disabled === 'true';
    const select = item.querySelector('[data-block-repeater-select]');
    const parametersEl = item.querySelector('[data-block-repeater-parameters]');

    if (!select || !parametersEl || select.dataset.bound === 'true') {
        return;
    }

    select.dataset.bound = 'true';

    const initialValues = (() => {
        try {
            return JSON.parse(parametersEl.dataset.initialValues || '{}');
        } catch {
            return {};
        }
    })();

    const rowIndex = item.dataset.index;
    const baseName = getRowBaseName(repeater, rowIndex);
    item.dataset.baseName = baseName;

    if (hasRenderedParameters(parametersEl) && select.value) {
        initRichTextEditors(parametersEl);
        initMediaFinders(parametersEl);
        initUrlParameters(parametersEl);
    } else {
        renderParameters(parametersEl, catalog, select.value, baseName, initialValues, isDisabled);
    }

    select.addEventListener('change', () => {
        const index = item.dataset.index;
        const block = findBlock(catalog, select.value);

        renderParameters(
            parametersEl,
            catalog,
            select.value,
            getRowBaseName(repeater, index),
            defaultValuesForBlock(block),
            isDisabled
        );
    });

    const removeBtn = item.querySelector('[data-block-repeater-remove]');

    if (removeBtn && removeBtn.dataset.bound !== 'true') {
        removeBtn.dataset.bound = 'true';
        removeBtn.addEventListener('click', () => {
            const min = parseInt(repeater.dataset.min || '0', 10);
            const itemsEl = repeater.querySelector('[data-block-repeater-items]');
            const count = itemsEl.querySelectorAll(':scope > [data-block-repeater-item]').length;

            if (count <= min) {
                return;
            }

            item.remove();
            reindexBlockRepeater(repeater);
        });
    }
}

export function initPageBlockRepeater() {
    document.querySelectorAll('[data-block-repeater]').forEach((repeater) => {
        if (repeater.dataset.initialized === 'true') {
            return;
        }

        repeater.dataset.initialized = 'true';

        const itemsEl = repeater.querySelector('[data-block-repeater-items]');
        const templateEl = repeater.querySelector('[data-block-repeater-prototype]');
        const addBtn = repeater.querySelector('[data-block-repeater-add]');
        const max = repeater.dataset.max ? parseInt(repeater.dataset.max, 10) : null;

        itemsEl.querySelectorAll(':scope > [data-block-repeater-item]').forEach((item) => {
            bindBlockRepeaterRow(repeater, item);
        });

        addBtn?.addEventListener('click', () => {
            if (!templateEl) {
                return;
            }

            const count = itemsEl.querySelectorAll(':scope > [data-block-repeater-item]').length;

            if (max !== null && count >= max) {
                return;
            }

            const clone = templateEl.content.cloneNode(true);
            const item = clone.querySelector('[data-block-repeater-item]');

            if (!item) {
                return;
            }

            item.dataset.index = String(count);
            itemsEl.appendChild(item);
            itemsEl.hidden = false;
            reindexBlockRepeater(repeater);
            bindBlockRepeaterRow(repeater, item);
        });
    });
}
