function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function parameterTipHtml(tip) {
    const text = String(tip ?? '').trim();

    if (! text) {
        return '';
    }

    return `<div class="form-text">${escapeHtml(text)}</div>`;
}

function resolveNativeInputType(type) {
    const nativeTypes = [
        'text',
        'number',
        'email',
        'color',
        'password',
        'date',
        'datetime-local',
        'file',
        'hidden',
    ];

    return nativeTypes.includes(type) ? type : 'text';
}

export function buildScalarParameterField({
    fieldName,
    label,
    type = 'text',
    value = '',
    defaultValue = '',
    tip = '',
    required = false,
    disabled = false,
    options = [],
    colClass = 'col-md-6',
    controlClass = 'form-control',
}) {
    const resolvedValue = value ?? defaultValue ?? '';
    const requiredAttr = required ? ' required' : '';
    const disabledAttr = disabled ? ' disabled' : '';
    const tipHtml = parameterTipHtml(tip);
    const labelHtml = escapeHtml(label);

    if (type === 'textarea' || type === 'code') {
        const codeClass = type === 'code' ? ' font-monospace' : '';

        return `
            <div class="col-12">
                <label class="form-label">${labelHtml}</label>
                <textarea class="${controlClass}${codeClass}" name="${fieldName}" rows="${type === 'code' ? 4 : 3}"${requiredAttr}${disabledAttr}>${escapeHtml(resolvedValue)}</textarea>
                ${tipHtml}
            </div>
        `;
    }

    if (type === 'richtext') {
        const fieldId = `richtext-${fieldName.replace(/[^a-zA-Z0-9_-]/g, '-')}`;
        const textareaContent = String(resolvedValue).replace(/<\/textarea/gi, '&lt;/textarea');

        return `
            <div class="col-12">
                <label class="form-label" for="${fieldId}">${labelHtml}</label>
                <textarea class="d-none" id="${fieldId}" name="${fieldName}"${requiredAttr}${disabledAttr}>${textareaContent}</textarea>
                <div class="loom-rich-text-editor"
                     data-rich-text-editor
                     data-target="${fieldId}"
                     ${disabled ? 'data-disabled="true"' : ''}></div>
                ${tipHtml}
            </div>
        `;
    }

    if (type === 'checkbox') {
        const checked = resolvedValue === true || resolvedValue === '1' || resolvedValue === 1 || resolvedValue === 'on';

        return `
            <div class="${colClass}">
                <div class="form-check mt-4">
                    <input type="hidden" name="${fieldName}" value="0"${disabledAttr}>
                    <input type="checkbox" class="form-check-input" name="${fieldName}" value="1"${checked ? ' checked' : ''}${disabledAttr}>
                    <label class="form-check-label">${labelHtml}</label>
                </div>
                ${tipHtml}
            </div>
        `;
    }

    if (type === 'select') {
        const optionItems = Array.isArray(options) ? options : [];
        const optionsHtml = optionItems.map((option) => {
            const optionValue = typeof option === 'object' ? (option.value ?? option.label) : option;
            const optionLabel = typeof option === 'object' ? (option.label ?? option.value) : option;
            const selected = String(resolvedValue) === String(optionValue) ? ' selected' : '';

            return `<option value="${escapeHtml(optionValue)}"${selected}>${escapeHtml(optionLabel)}</option>`;
        }).join('');

        return `
            <div class="${colClass}">
                <label class="form-label">${labelHtml}</label>
                <select class="${controlClass.replace('form-control', 'form-select')}" name="${fieldName}"${requiredAttr}${disabledAttr}>
                    <option value="" disabled ${resolvedValue === '' ? 'selected' : ''}>Select…</option>
                    ${optionsHtml}
                </select>
                ${tipHtml}
            </div>
        `;
    }

    if (type === 'radio') {
        const optionItems = Array.isArray(options) ? options : [];
        const radiosHtml = optionItems.map((option, index) => {
            const optionValue = typeof option === 'object' ? (option.value ?? option.label) : option;
            const optionLabel = typeof option === 'object' ? (option.label ?? option.value) : option;
            const inputId = `${fieldName.replace(/[^a-zA-Z0-9_-]/g, '-')}-${index}`;
            const checked = String(resolvedValue) === String(optionValue) ? ' checked' : '';

            return `
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="${inputId}" name="${fieldName}" value="${escapeHtml(optionValue)}"${checked}${disabledAttr}>
                    <label class="form-check-label" for="${inputId}">${escapeHtml(optionLabel)}</label>
                </div>
            `;
        }).join('');

        return `
            <div class="${colClass}">
                <fieldset>
                    <legend class="form-label mb-1">${labelHtml}</legend>
                    ${radiosHtml}
                </fieldset>
                ${tipHtml}
            </div>
        `;
    }

    if (type === 'hidden') {
        return `<input type="hidden" name="${fieldName}" value="${escapeHtml(resolvedValue)}"${disabledAttr}>`;
    }

    const inputType = resolveNativeInputType(type);
    const fileValueAttr = type === 'file' ? '' : ` value="${escapeHtml(resolvedValue)}"`;

    return `
        <div class="${colClass}">
            <label class="form-label">${labelHtml}</label>
            <input type="${inputType}" class="${controlClass}" name="${fieldName}"${fileValueAttr}${requiredAttr}${disabledAttr}>
            ${tipHtml}
        </div>
    `;
}
