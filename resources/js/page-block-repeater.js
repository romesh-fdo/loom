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

function findBlock(catalog, blockId) {
    return catalog.find((block) => String(block.id) === String(blockId)) ?? null;
}

function buildParameterInput(name, parameter, value, disabled) {
    const type = parameter.type ?? 'text';
    const label = escapeHtml(parameter.label ?? parameter.name);
    const fieldName = `${name}[values][${parameter.name}]`;
    const required = parameter.required ? ' required' : '';
    const disabledAttr = disabled ? ' disabled' : '';
    const resolvedValue = value ?? parameter.default ?? '';

    if (type === 'textarea') {
        return `
            <div class="col-12">
                <label class="form-label">${label}</label>
                <textarea class="form-control" name="${fieldName}" rows="3"${required}${disabledAttr}>${escapeHtml(resolvedValue)}</textarea>
            </div>
        `;
    }

    if (type === 'checkbox') {
        const checked = resolvedValue === true || resolvedValue === '1' || resolvedValue === 1 || resolvedValue === 'on';

        return `
            <div class="col-md-6">
                <div class="form-check mt-4">
                    <input type="hidden" name="${fieldName}" value="0"${disabledAttr}>
                    <input type="checkbox" class="form-check-input" name="${fieldName}" value="1"${checked ? ' checked' : ''}${disabledAttr}>
                    <label class="form-check-label">${label}</label>
                </div>
            </div>
        `;
    }

    if (type === 'select') {
        const options = Array.isArray(parameter.options) ? parameter.options : [];
        const optionsHtml = options.map((option) => {
            const optionValue = typeof option === 'object' ? (option.value ?? option.label) : option;
            const optionLabel = typeof option === 'object' ? (option.label ?? option.value) : option;
            const selected = String(resolvedValue) === String(optionValue) ? ' selected' : '';

            return `<option value="${escapeHtml(optionValue)}"${selected}>${escapeHtml(optionLabel)}</option>`;
        }).join('');

        return `
            <div class="col-md-6">
                <label class="form-label">${label}</label>
                <select class="form-select" name="${fieldName}"${required}${disabledAttr}>
                    <option value="" disabled ${resolvedValue === '' ? 'selected' : ''}>Select…</option>
                    ${optionsHtml}
                </select>
            </div>
        `;
    }

    const inputType = ['number', 'email', 'color'].includes(type) ? type : 'text';

    return `
        <div class="col-md-6">
            <label class="form-label">${label}</label>
            <input type="${inputType}" class="form-control" name="${fieldName}" value="${escapeHtml(resolvedValue)}"${required}${disabledAttr}>
        </div>
    `;
}

function renderParameters(container, catalog, blockId, baseName, initialValues, disabled) {
    const block = findBlock(catalog, blockId);
    container.innerHTML = '';

    if (!block || !Array.isArray(block.parameters) || block.parameters.length === 0) {
        container.innerHTML = blockId
            ? '<div class="col-12"><p class="text-muted small mb-0">This block has no dynamic parameters.</p></div>'
            : '';

        return;
    }

    const values = initialValues && typeof initialValues === 'object' ? initialValues : {};

    block.parameters.forEach((parameter) => {
        container.insertAdjacentHTML(
            'beforeend',
            buildParameterInput(baseName, parameter, values[parameter.name] ?? '', disabled)
        );
    });
}

function getRowBaseName(repeater, rowIndex) {
    const baseName = repeater.dataset.name;

    return `${baseName}[${rowIndex}]`;
}

function reindexBlockRepeater(repeater) {
    const itemsEl = repeater.querySelector('[data-block-repeater-items]');
    const catalog = parseCatalog(repeater.dataset.blocksCatalog);
    const itemLabel = repeater.dataset.itemLabel || 'Block';
    const isDisabled = repeater.dataset.disabled === 'true';
    const items = itemsEl.querySelectorAll(':scope > [data-block-repeater-item]');

    items.forEach((item, index) => {
        item.dataset.index = String(index);

        const title = item.querySelector('[data-block-repeater-item-label]');

        if (title) {
            title.textContent = `${itemLabel} ${index + 1}`;
        }

        const select = item.querySelector('[data-block-repeater-select]');
        const parametersEl = item.querySelector('[data-block-repeater-parameters]');
        const baseName = getRowBaseName(repeater, index);

        if (select) {
            select.name = `${baseName}[block_id]`;
        }

        if (select && parametersEl) {
            const currentValues = {};

            parametersEl.querySelectorAll('[name*="[values]"]').forEach((input) => {
                const match = input.name.match(/\[values\]\[([^\]]+)\]/);

                if (!match) {
                    return;
                }

                if (input.type === 'checkbox') {
                    currentValues[match[1]] = input.checked ? input.value : '0';
                } else {
                    currentValues[match[1]] = input.value;
                }
            });

            renderParameters(parametersEl, catalog, select.value, baseName, currentValues, isDisabled);
        }
    });

    if (items.length === 0) {
        itemsEl.hidden = true;
    } else {
        itemsEl.hidden = false;
    }
}

function bindBlockRepeaterRow(repeater, item) {
    const catalog = parseCatalog(repeater.dataset.blocksCatalog);
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

    renderParameters(parametersEl, catalog, select.value, baseName, initialValues, isDisabled);

    select.addEventListener('change', () => {
        const index = item.dataset.index;
        renderParameters(parametersEl, catalog, select.value, getRowBaseName(repeater, index), {}, isDisabled);
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
