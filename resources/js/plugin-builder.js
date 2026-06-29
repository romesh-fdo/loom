import * as bootstrap from 'bootstrap';

function reindexPluginBuilderFields(container) {
    container.querySelectorAll('[data-plugin-builder-field]').forEach((row, index) => {
        row.dataset.index = String(index);

        row.querySelectorAll('[name]').forEach((input) => {
            const name = input.getAttribute('name');

            if (!name || !name.startsWith('fields[')) {
                return;
            }

            input.name = name.replace(/^fields\[\d+]/, `fields[${index}]`);
        });

        reindexValidationRules(row);
    });
}

function slugifyFieldName(label) {
    return label
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .replace(/^(\d)/, '_$1');
}

function slugifyPluginSlug(label) {
    const slug = label
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    if (slug === '') {
        return '';
    }

    return /^[a-z]/.test(slug) ? slug : `plugin-${slug}`;
}

function studlyFromSlug(slug) {
    const studly = slug.split('-').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join('');

    return studly.replace(/s$/, '').replace(/ies$/, 'y').replace(/items$/, 'Item') || studly;
}

function tableFromSlug(slug) {
    return slug.replace(/-/g, '_').replace(/([^s])$/, '$1s');
}

function fillDerivedPluginFields(form, slug) {
    const routeSlug = form.querySelector('#route_slug');
    const modelClass = form.querySelector('#model_class');
    const tableName = form.querySelector('#table_name');

    if (routeSlug && form.dataset.routeManual !== 'true') {
        routeSlug.value = slug;
    }

    if (modelClass && form.dataset.modelManual !== 'true') {
        modelClass.value = studlyFromSlug(slug);
    }

    if (tableName && form.dataset.tableManual !== 'true') {
        tableName.value = tableFromSlug(slug);
    }
}

function syncPluginSlugManualState(form) {
    const pluginLabel = form.querySelector('[data-plugin-builder-plugin-label]');
    const pluginSlug = form.querySelector('[data-plugin-builder-plugin-slug]');

    if (!pluginLabel || !pluginSlug) {
        return;
    }

    const autoSlug = slugifyPluginSlug(pluginLabel.value);

    if (!pluginSlug.value || pluginSlug.value === autoSlug) {
        form.dataset.slugManual = 'false';
        form.dataset.lastAutoSlug = autoSlug;
    } else {
        form.dataset.slugManual = 'true';
    }
}

function syncDerivedManualState(form) {
    const pluginSlug = form.querySelector('#plugin_slug') ?? form.querySelector('[data-plugin-builder-plugin-slug]');
    const routeSlug = form.querySelector('#route_slug');
    const modelClass = form.querySelector('#model_class');
    const tableName = form.querySelector('#table_name');

    if (!pluginSlug) {
        return;
    }

    const slug = pluginSlug.value.trim();

    if (routeSlug) {
        form.dataset.routeManual = routeSlug.value && routeSlug.value !== slug ? 'true' : 'false';
    }

    if (modelClass) {
        form.dataset.modelManual = modelClass.value && modelClass.value !== studlyFromSlug(slug) ? 'true' : 'false';
    }

    if (tableName) {
        form.dataset.tableManual = tableName.value && tableName.value !== tableFromSlug(slug) ? 'true' : 'false';
    }
}

function syncFieldNameManualState(fieldRow) {
    const labelInput = fieldRow.querySelector('[data-plugin-builder-field-label]');
    const nameInput = fieldRow.querySelector('[data-plugin-builder-field-name]');

    if (!labelInput || !nameInput) {
        return;
    }

    const autoName = slugifyFieldName(labelInput.value);

    if (!nameInput.value || nameInput.value === autoName) {
        fieldRow.dataset.nameManual = 'false';
        fieldRow.dataset.lastAutoName = autoName;
    } else {
        fieldRow.dataset.nameManual = 'true';
    }
}

function reindexValidationRules(fieldRow) {
    const container = fieldRow.querySelector('[data-plugin-builder-validation-rules-list]');
    const fieldIndex = fieldRow.dataset.index;

    if (!container || fieldIndex === undefined) {
        return;
    }

    container.querySelectorAll('[data-plugin-builder-validation-rule]').forEach((row, ruleIndex) => {
        row.querySelectorAll('input[name]').forEach((input) => {
            const suffix = input.name.includes('[rule]') ? '[rule]' : '[message]';
            input.name = `fields[${fieldIndex}][validation_rules][${ruleIndex}]${suffix}`;
        });
    });
}

function appendValidationRule(fieldRow) {
    const template = document.querySelector('[data-plugin-builder-validation-rule-template]');
    const list = fieldRow.querySelector('[data-plugin-builder-validation-rules-list]');
    const fieldIndex = fieldRow.dataset.index;

    if (!template?.content || !list || fieldIndex === undefined) {
        return;
    }

    const ruleIndex = list.querySelectorAll('[data-plugin-builder-validation-rule]').length;
    const html = template.innerHTML
        .replace(/__INDEX__/g, fieldIndex)
        .replace(/__RULE_INDEX__/g, String(ruleIndex));
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const row = wrapper.firstElementChild;

    if (row) {
        list.appendChild(row);
        reindexValidationRules(fieldRow);
    }
}

function initIconPicker(root) {
    const iconsUrl = root.dataset.iconsUrl;
    const hiddenInput = root.querySelector('#plugin_icon');
    const preview = root.querySelector('[data-plugin-builder-icon-preview]');
    const label = root.querySelector('[data-plugin-builder-icon-label]');
    const list = root.querySelector('[data-plugin-builder-icon-list]');
    const searchInput = root.querySelector('[data-plugin-builder-icon-search]');
    const trigger = root.querySelector('.plugin-builder-icon-picker__trigger');

    if (!iconsUrl || !hiddenInput || !list || !searchInput) {
        return;
    }

    let searchTimer = null;
    let activeQuery = null;

    function setSelected(iconName) {
        hiddenInput.value = iconName;

        if (preview) {
            preview.className = `bi ${iconName}`;
        }

        if (label) {
            label.textContent = iconName;
        }
    }

    function renderIcons(icons) {
        list.innerHTML = '';

        if (!icons.length) {
            list.innerHTML = '<p class="text-muted small mb-0 px-2 py-3 text-center">No icons found.</p>';
            return;
        }

        icons.forEach((icon) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'plugin-builder-icon-picker__option';
            button.dataset.iconName = icon.name;
            button.setAttribute('role', 'option');
            button.innerHTML = `
                <i class="bi ${icon.name}"></i>
                <span class="plugin-builder-icon-picker__option-label">${icon.name}</span>
                <span class="plugin-builder-icon-picker__option-meta">${icon.label}</span>
            `;

            if (icon.name === hiddenInput.value) {
                button.classList.add('is-active');
            }

            button.addEventListener('click', () => {
                setSelected(icon.name);
                bootstrap.Dropdown.getOrCreateInstance(trigger)?.hide();
            });

            list.appendChild(button);
        });
    }

    async function loadIcons(query = '') {
        activeQuery = query;
        list.innerHTML = '<p class="text-muted small mb-0 px-2 py-3 text-center">Loading icons…</p>';

        try {
            const url = new URL(iconsUrl, window.location.origin);
            url.searchParams.set('q', query);

            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Failed to load icons');
            }

            const data = await response.json();

            if (activeQuery !== query) {
                return;
            }

            renderIcons(data.icons ?? []);
        } catch {
            list.innerHTML = '<p class="text-muted small mb-0 px-2 py-3 text-center">Could not load icons.</p>';
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadIcons(searchInput.value.trim()), 200);
    });

    trigger?.addEventListener('show.bs.dropdown', () => {
        searchInput.value = '';
        loadIcons('');
        setTimeout(() => searchInput.focus(), 0);
    });
}

const PLUGIN_BUILDER_SAVE_STEPS = [
    { label: 'Validating…', percent: 12 },
    { label: 'Writing schema files…', percent: 32 },
    { label: 'Updating plugin files…', percent: 52 },
    { label: 'Generating migrations…', percent: 68 },
    { label: 'Running migrations…', percent: 84 },
];

function setPluginBuilderSaveProgress(overlay, percent, label) {
    const progressBar = overlay?.querySelector('[data-plugin-builder-save-progress]');
    const stepEl = overlay?.querySelector('[data-plugin-builder-save-step]');

    if (progressBar) {
        progressBar.style.width = `${percent}%`;
        progressBar.setAttribute('aria-valuenow', String(percent));
    }

    if (stepEl && label) {
        stepEl.textContent = label;
    }
}

function showPluginBuilderSaveOverlay(overlay) {
    if (!overlay) {
        return;
    }

    overlay.hidden = false;
    overlay.setAttribute('aria-hidden', 'false');
    setPluginBuilderSaveProgress(overlay, 5, PLUGIN_BUILDER_SAVE_STEPS[0].label);
}

function hidePluginBuilderSaveOverlay(overlay) {
    if (!overlay) {
        return;
    }

    overlay.hidden = true;
    overlay.setAttribute('aria-hidden', 'true');
}

function initPluginBuilderSave(form) {
    const overlay = document.querySelector('[data-plugin-builder-save-overlay]');
    const submitBtn = form.querySelector('[data-plugin-builder-submit]');
    const submitLabel = submitBtn?.textContent?.trim() ?? 'Save plugin';

    let progressTimer = null;
    let stepIndex = 0;

    function clearSaveProgressTimer() {
        if (progressTimer !== null) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
    }

    function startSaveProgressTimer() {
        clearSaveProgressTimer();
        stepIndex = 0;

        progressTimer = setInterval(() => {
            if (stepIndex >= PLUGIN_BUILDER_SAVE_STEPS.length - 1) {
                return;
            }

            stepIndex += 1;
            const step = PLUGIN_BUILDER_SAVE_STEPS[stepIndex];
            setPluginBuilderSaveProgress(overlay, step.percent, step.label);
        }, 900);
    }

    function resetSaveUi() {
        clearSaveProgressTimer();
        form.dataset.saving = 'false';

        if (submitBtn) {
            submitBtn.removeAttribute('disabled');
            submitBtn.textContent = submitLabel;
        }

        hidePluginBuilderSaveOverlay(overlay);
    }

    form.addEventListener('submit', async (event) => {
        if (form.dataset.saving === 'true') {
            event.preventDefault();
            return;
        }

        event.preventDefault();

        form.dataset.saving = 'true';

        if (submitBtn) {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.textContent = 'Saving…';
        }

        showPluginBuilderSaveOverlay(overlay);
        startSaveProgressTimer();

        try {
            const response = await fetch(form.action, {
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                credentials: 'same-origin',
            });

            clearSaveProgressTimer();

            if (!response.ok) {
                throw new Error(`Save failed (${response.status})`);
            }

            setPluginBuilderSaveProgress(overlay, 100, 'Done!');

            await new Promise((resolve) => {
                setTimeout(resolve, 350);
            });

            window.location.href = response.url;
        } catch {
            resetSaveUi();
            window.alert('Save failed. Please try again.');
        }
    });
}

export function initPluginBuilder() {
    const form = document.querySelector('[data-plugin-builder-form]');

    if (!form) {
        return;
    }

    const fieldsContainer = form.querySelector('[data-plugin-builder-fields]');
    const template = form.querySelector('[data-plugin-builder-field-template]');
    const addBtn = form.querySelector('[data-plugin-builder-add-field]');

    addBtn?.addEventListener('click', () => {
        if (!template?.content || !fieldsContainer) {
            return;
        }

        const index = fieldsContainer.querySelectorAll('[data-plugin-builder-field]').length;
        const html = template.innerHTML.replace(/__INDEX__/g, String(index));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;

        if (row) {
            fieldsContainer.appendChild(row);
            reindexPluginBuilderFields(fieldsContainer);
            syncFieldNameManualState(row);
        }
    });

    form.addEventListener('input', (event) => {
        const pluginLabel = event.target.closest('[data-plugin-builder-plugin-label]');

        if (pluginLabel) {
            const pluginSlug = form.querySelector('[data-plugin-builder-plugin-slug]');

            if (pluginSlug && form.dataset.slugManual !== 'true') {
                const autoSlug = slugifyPluginSlug(pluginLabel.value);
                pluginSlug.value = autoSlug;
                form.dataset.lastAutoSlug = autoSlug;
                fillDerivedPluginFields(form, autoSlug);
            }

            return;
        }

        const pluginSlugInput = event.target.closest('[data-plugin-builder-plugin-slug]');

        if (pluginSlugInput) {
            syncPluginSlugManualState(form);

            if (form.dataset.slugManual !== 'true') {
                fillDerivedPluginFields(form, pluginSlugInput.value.trim());
            }

            return;
        }

        if (event.target.id === 'route_slug') {
            syncDerivedManualState(form);
            return;
        }

        if (event.target.id === 'model_class') {
            syncDerivedManualState(form);
            return;
        }

        if (event.target.id === 'table_name') {
            syncDerivedManualState(form);
            return;
        }

        const labelInput = event.target.closest('[data-plugin-builder-field-label]');

        if (labelInput) {
            const fieldRow = labelInput.closest('[data-plugin-builder-field]');
            const nameInput = fieldRow?.querySelector('[data-plugin-builder-field-name]');

            if (!fieldRow || !nameInput || fieldRow.dataset.nameManual === 'true') {
                return;
            }

            const autoName = slugifyFieldName(labelInput.value);
            nameInput.value = autoName;
            fieldRow.dataset.lastAutoName = autoName;

            return;
        }

        const nameInput = event.target.closest('[data-plugin-builder-field-name]');

        if (nameInput) {
            const fieldRow = nameInput.closest('[data-plugin-builder-field]');

            if (fieldRow) {
                syncFieldNameManualState(fieldRow);
            }
        }
    });

    form.addEventListener('click', (event) => {
        const removeFieldBtn = event.target.closest('[data-plugin-builder-remove-field]');

        if (removeFieldBtn) {
            const row = removeFieldBtn.closest('[data-plugin-builder-field]');
            row?.remove();
            reindexPluginBuilderFields(fieldsContainer);
            return;
        }

        const addRuleBtn = event.target.closest('[data-plugin-builder-add-validation-rule]');

        if (addRuleBtn) {
            const fieldRow = addRuleBtn.closest('[data-plugin-builder-field]');

            if (fieldRow) {
                appendValidationRule(fieldRow);
            }

            return;
        }

        const removeRuleBtn = event.target.closest('[data-plugin-builder-remove-validation-rule]');

        if (removeRuleBtn) {
            const fieldRow = removeRuleBtn.closest('[data-plugin-builder-field]');
            removeRuleBtn.closest('[data-plugin-builder-validation-rule]')?.remove();

            if (fieldRow) {
                reindexValidationRules(fieldRow);
            }
        }
    });

    const pluginSlug = form.querySelector('#plugin_slug');
    const routeSlug = form.querySelector('#route_slug');
    const modelClass = form.querySelector('#model_class');
    const tableName = form.querySelector('#table_name');

    syncPluginSlugManualState(form);
    syncDerivedManualState(form);

    pluginSlug?.addEventListener('blur', () => {
        const slug = pluginSlug.value.trim();

        if (!slug) {
            return;
        }

        fillDerivedPluginFields(form, slug);
        syncDerivedManualState(form);
    });

    fieldsContainer?.querySelectorAll('[data-plugin-builder-field]').forEach((fieldRow) => {
        reindexValidationRules(fieldRow);
        syncFieldNameManualState(fieldRow);
    });

    form.querySelectorAll('[data-plugin-builder-icon-picker]').forEach((picker) => {
        initIconPicker(picker);
    });

    initPluginBuilderSave(form);
}
