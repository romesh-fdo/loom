import * as bootstrap from 'bootstrap';

let parameterModalInstance = null;
let activeParameterField = null;
let parameterModalState = { url: '', class: '', id: '', target: '' };
let parameterModalBound = false;

function getParameterModal() {
    return document.querySelector('[data-url-parameter-modal]');
}

function readFieldState(field) {
    return {
        url: field.querySelector('[data-url-param-url]')?.value ?? '',
        class: field.querySelector('[data-url-param-class]')?.value ?? '',
        id: field.querySelector('[data-url-param-id]')?.value ?? '',
        target: field.querySelector('[data-url-param-target]')?.value ?? '',
    };
}

function writeFieldState(field, state) {
    const urlInput = field.querySelector('[data-url-param-url]');
    const classInput = field.querySelector('[data-url-param-class]');
    const idInput = field.querySelector('[data-url-param-id]');
    const targetInput = field.querySelector('[data-url-param-target]');

    if (urlInput) {
        urlInput.value = state.url ?? '';
        urlInput.dispatchEvent(new Event('input', { bubbles: true }));
        urlInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (classInput) {
        classInput.value = state.class ?? '';
    }

    if (idInput) {
        idInput.value = state.id ?? '';
    }

    if (targetInput) {
        targetInput.value = state.target === '_blank' ? '_blank' : '';
    }

    updateTriggerPreview(field, state.url);
}

function show(el) {
    el?.classList.remove('d-none');
}

function hide(el) {
    el?.classList.add('d-none');
}

function updateTriggerPreview(field, url) {
    const hasUrl = String(url).trim() !== '';
    const placeholder = field.querySelector('[data-url-parameter-placeholder]');
    const preview = field.querySelector('[data-url-parameter-preview]');
    const label = field.querySelector('[data-url-parameter-preview-label]');
    const clearButton = field.querySelector('[data-url-parameter-clear]');

    if (hasUrl) {
        hide(placeholder);
        show(preview);
        if (label) {
            label.textContent = url;
        }
        show(clearButton);
    } else {
        show(placeholder);
        hide(preview);
        if (label) {
            label.textContent = '';
        }
        hide(clearButton);
    }
}

function bindParameterModal() {
    if (parameterModalBound) {
        return;
    }

    const modal = getParameterModal();

    if (!modal) {
        return;
    }

    parameterModalBound = true;
    parameterModalInstance = bootstrap.Modal.getOrCreateInstance(modal);

    const urlInput = modal.querySelector('[data-url-parameter-modal-url]');
    const classInput = modal.querySelector('[data-url-parameter-modal-class]');
    const idInput = modal.querySelector('[data-url-parameter-modal-id]');
    const targetInput = modal.querySelector('[data-url-parameter-modal-target]');
    const applyButton = modal.querySelector('[data-url-parameter-modal-apply]');
    const clearButton = modal.querySelector('[data-url-parameter-modal-clear]');

    clearButton?.addEventListener('click', () => {
        parameterModalState = { url: '', class: '', id: '', target: '' };
        urlInput.value = '';
        classInput.value = '';
        idInput.value = '';
        targetInput.checked = false;
    });

    applyButton?.addEventListener('click', () => {
        if (!activeParameterField) {
            return;
        }

        parameterModalState = {
            url: urlInput?.value?.trim() ?? '',
            class: classInput?.value?.trim() ?? '',
            id: idInput?.value?.trim() ?? '',
            target: targetInput?.checked ? '_blank' : '',
        };

        writeFieldState(activeParameterField, parameterModalState);
        parameterModalInstance.hide();
    });

    modal.addEventListener('hidden.bs.modal', () => {
        activeParameterField = null;
    });
}

function openUrlParameterModal(field) {
    const modal = getParameterModal();

    if (!modal || field.dataset.disabled === 'true') {
        return;
    }

    bindParameterModal();

    activeParameterField = field;
    parameterModalState = readFieldState(field);

    modal.querySelector('[data-url-parameter-modal-url]').value = parameterModalState.url;
    modal.querySelector('[data-url-parameter-modal-class]').value = parameterModalState.class;
    modal.querySelector('[data-url-parameter-modal-id]').value = parameterModalState.id;
    modal.querySelector('[data-url-parameter-modal-target]').checked = parameterModalState.target === '_blank';

    const label = field.querySelector('.form-label')?.textContent?.trim() || 'Link';
    const title = modal.querySelector('#loom-url-parameter-modal-label');

    if (title) {
        title.textContent = label;
    }

    parameterModalInstance.show();
}

function initUrlParameterField(field) {
    if (field.dataset.urlParameterInit === 'true') {
        return;
    }

    field.dataset.urlParameterInit = 'true';

    const openButton = field.querySelector('[data-url-parameter-open]');
    const clearButton = field.querySelector('[data-url-parameter-clear]');

    updateTriggerPreview(field, readFieldState(field).url);

    openButton?.addEventListener('click', (event) => {
        event.preventDefault();
        openUrlParameterModal(field);
    });

    clearButton?.addEventListener('click', (event) => {
        event.preventDefault();
        writeFieldState(field, { url: '', class: '', id: '', target: '' });
    });
}

export function initUrlParameters(root = document) {
    bindParameterModal();
    root.querySelectorAll('[data-url-parameter-field]').forEach(initUrlParameterField);
}

export function resolveUrlCompoundValue(value) {
    if (typeof value === 'string') {
        return {
            url: value,
            class: '',
            id: '',
            target: '',
        };
    }

    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const target = value.target === '_blank' || value.open_in_new_tab === true || value.open_in_new_tab === '1'
            ? '_blank'
            : '';

        return {
            url: value.url ?? '',
            class: value.class ?? value.className ?? '',
            id: value.id ?? '',
            target,
        };
    }

    return {
        url: '',
        class: '',
        id: '',
        target: '',
    };
}

export function buildUrlParameterFieldHtml({
    fieldName,
    label,
    value = '',
    tip = '',
    disabled = false,
    colClass = 'col-md-6',
}) {
    const compound = resolveUrlCompoundValue(value);
    const disabledAttr = disabled ? ' disabled' : '';
    const tipHtml = tip ? `<div class="form-text">${String(tip).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>` : '';
    const labelHtml = String(label).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const hasUrl = String(compound.url).trim() !== '';

    const escapeAttr = (text) => String(text)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');

    return `
        <div class="${colClass}">
            <div class="loom-url-parameter-field"
                 data-url-parameter-field
                 ${disabled ? 'data-disabled="true"' : ''}>
                <label class="form-label">${labelHtml}</label>
                <input type="hidden" name="${fieldName}[url]" value="${escapeAttr(compound.url)}" data-url-param-url>
                <input type="hidden" name="${fieldName}[class]" value="${escapeAttr(compound.class)}" data-url-param-class>
                <input type="hidden" name="${fieldName}[id]" value="${escapeAttr(compound.id)}" data-url-param-id>
                <input type="hidden" name="${fieldName}[target]" value="${escapeAttr(compound.target)}" data-url-param-target>
                <div class="loom-media-parameter-trigger-wrap">
                    <button type="button"
                            class="loom-media-parameter-trigger"
                            data-url-parameter-open
                            ${disabledAttr}>
                        <span class="loom-media-parameter-trigger__placeholder ${hasUrl ? 'd-none' : ''}"
                              data-url-parameter-placeholder>
                            <i class="bi bi-link-45deg" aria-hidden="true"></i>
                            Set link
                        </span>
                        <span class="loom-media-parameter-trigger__preview ${hasUrl ? '' : 'd-none'}"
                              data-url-parameter-preview>
                            <span class="loom-media-parameter-trigger__preview-file">
                                <i class="bi bi-link-45deg" aria-hidden="true"></i>
                            </span>
                            <span class="loom-media-parameter-trigger__filename" data-url-parameter-preview-label>
                                ${escapeAttr(compound.url)}
                            </span>
                        </span>
                    </button>
                    ${disabled ? '' : `
                        <button type="button"
                                class="loom-media-parameter-clear ${hasUrl ? '' : 'd-none'}"
                                data-url-parameter-clear
                                aria-label="Clear link">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    `}
                </div>
            </div>
            ${tipHtml}
        </div>
    `;
}
