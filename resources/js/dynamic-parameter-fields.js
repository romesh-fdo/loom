import { buildUrlParameterFieldHtml } from './url-parameter';

export function richTextFieldId(fieldName) {
    return `richtext-${String(fieldName).replace(/[^a-zA-Z0-9_-]/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '')}`;
}

export const ALLOWED_COL_CLASSES = [
    'col-12',
    'col-md-3',
    'col-md-4',
    'col-md-6',
    'col-md-8',
    'col-md-9',
];

export function isMediaCompoundValue(value) {
    return Boolean(
        value
        && typeof value === 'object'
        && !Array.isArray(value)
        && ('url' in value || 'alt' in value || 'class' in value)
    );
}

export function isUrlCompoundValue(value) {
    return Boolean(
        value
        && typeof value === 'object'
        && !Array.isArray(value)
        && !('alt' in value)
        && ('id' in value || 'target' in value)
    );
}

export function isUrlParameterType(type) {
    return type === 'url';
}

export function isMediaParameterType(type) {
    return type === 'media_selector' || type === 'media_attach' || type === 'media_finder';
}

export function resolveEffectiveParameterType(parameter, value = undefined) {
    const type = parameter?.type ?? 'text';

    if (isUrlParameterType(type)) {
        return 'url';
    }

    if (isMediaParameterType(type)) {
        return type === 'media_finder' ? 'media_selector' : type;
    }

    if (type === 'file') {
        return 'media_attach';
    }

    let candidate = value;

    if (candidate === undefined || candidate === null || candidate === '') {
        candidate = parameter?.default ?? null;
    }

    if (isUrlCompoundValue(candidate)) {
        return 'url';
    }

    if (isMediaCompoundValue(candidate)) {
        return 'media_selector';
    }

    return type;
}

export function defaultColClassForType(type) {
    return ['repeater', 'textarea', 'richtext', 'code', 'media_selector', 'media_attach', 'media_finder'].includes(type)
        ? 'col-12'
        : 'col-md-6';
}

export function resolveParameterColClass(parameter, value = undefined) {
    const type = resolveEffectiveParameterType(parameter, value);
    const colClass = parameter.colClass ?? defaultColClassForType(type);

    return ALLOWED_COL_CLASSES.includes(colClass) ? colClass : defaultColClassForType(type);
}

export function resolveParameterRow(parameter) {
    const row = parseInt(parameter.row ?? '1', 10);

    return Number.isFinite(row) && row >= 1 ? row : 1;
}

/**
 * @param {Array<object>} items
 * @param {(item: object) => number} getRow
 * @returns {Array<[number, Array<object>]>}
 */
export function groupByRow(items, getRow) {
    const groups = new Map();

    items.forEach((item) => {
        const row = getRow(item);
        const bucket = groups.get(row) ?? [];
        bucket.push(item);
        groups.set(row, bucket);
    });

    return [...groups.entries()].sort(([a], [b]) => a - b);
}

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
        'url',
        'color',
        'password',
        'date',
        'datetime-local',
        'file',
        'hidden',
    ];

    return nativeTypes.includes(type) ? type : 'text';
}

function resolveMediaCompoundValue(value) {
    if (value && typeof value === 'object' && ! Array.isArray(value)) {
        return {
            url: value.url ?? '',
            alt: value.alt ?? '',
            class: value.class ?? value.className ?? '',
        };
    }

    if (typeof value === 'string') {
        return {
            url: value,
            alt: '',
            class: '',
        };
    }

    return {
        url: '',
        alt: '',
        class: '',
    };
}

function mediaPreviewMarkup(url, previewId) {
    const hasValue = String(url).trim() !== '';
    let existingName = '';
    let existingIsImage = false;

    if (hasValue) {
        try {
            existingName = decodeURIComponent(new URL(url, window.location.origin).pathname.split('/').pop() || '');
        } catch {
            existingName = String(url).split('/').pop() || '';
        }

        existingIsImage = /\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i.test(existingName);
    }

    return `
        <div id="${previewId}"
             class="loom-file-preview ${hasValue ? '' : 'd-none'}"
             data-media-finder-preview>
            <div class="loom-file-preview__image ${hasValue && existingIsImage ? '' : 'd-none'}"
                 data-media-finder-preview-image>
                <img src="${existingIsImage ? escapeHtml(url) : ''}"
                     alt="${escapeHtml(existingName || 'File preview')}"
                     data-media-finder-preview-img>
            </div>
            <div class="loom-file-preview__file ${hasValue && !existingIsImage ? '' : 'd-none'}"
                 data-media-finder-preview-file>
                <i class="bi bi-file-earmark" aria-hidden="true"></i>
                <span data-media-finder-preview-name>${escapeHtml(existingName)}</span>
            </div>
        </div>
    `;
}

export function buildMediaParameterField({
    fieldName,
    label,
    type = 'media_selector',
    value = '',
    tip = '',
    required = false,
    disabled = false,
    colClass = 'col-12',
}) {
    const compound = resolveMediaCompoundValue(value);
    const disabledAttr = disabled ? ' disabled' : '';
    const tipHtml = parameterTipHtml(tip);
    const labelHtml = escapeHtml(label);
    const urlFieldName = `${fieldName}[url]`;
    const altFieldName = `${fieldName}[alt]`;
    const classFieldName = `${fieldName}[class]`;
    const hasUrl = String(compound.url).trim() !== '';
    const mode = type === 'media_attach' ? 'attach' : 'selector';

    let existingName = '';

    if (hasUrl) {
        try {
            existingName = decodeURIComponent(new URL(compound.url, window.location.origin).pathname.split('/').pop() || '');
        } catch {
            existingName = String(compound.url).split('/').pop() || '';
        }
    }

    const existingIsImage = hasUrl && /\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i.test(existingName);

    return `
        <div class="${colClass}">
            <div class="loom-media-parameter-field"
                 data-media-parameter-field
                 data-media-mode="${mode}"
                 ${disabled ? 'data-disabled="true"' : ''}>
                <label class="form-label">${labelHtml}</label>
                <input type="hidden" name="${urlFieldName}" value="${escapeHtml(compound.url)}" data-media-param-url>
                <input type="hidden" name="${altFieldName}" value="${escapeHtml(compound.alt)}" data-media-param-alt>
                <input type="hidden" name="${classFieldName}" value="${escapeHtml(compound.class)}" data-media-param-class>
                <div class="loom-media-parameter-trigger-wrap">
                    <button type="button"
                            class="loom-media-parameter-trigger ${hasUrl ? 'd-none' : ''}"
                            data-media-parameter-open
                            data-media-parameter-empty
                            ${disabledAttr}>
                        <span class="loom-media-parameter-trigger__placeholder">
                            <i class="bi bi-image" aria-hidden="true"></i>
                            Choose media
                        </span>
                    </button>
                    <div class="loom-media-parameter-display ${hasUrl ? '' : 'd-none'}"
                         data-media-parameter-display>
                        <div class="loom-media-parameter-display__visual">
                            <span class="loom-media-parameter-display__image ${hasUrl && existingIsImage ? '' : 'd-none'}"
                                  data-media-parameter-preview-image>
                                <img src="${existingIsImage ? escapeHtml(compound.url) : ''}"
                                     alt="${escapeHtml(existingName || 'Preview')}"
                                     data-media-parameter-preview-img>
                            </span>
                            <span class="loom-media-parameter-display__file ${hasUrl && !existingIsImage ? '' : 'd-none'}"
                                  data-media-parameter-preview-file>
                                <i class="bi bi-file-earmark" aria-hidden="true"></i>
                                <span data-media-parameter-preview-name>${escapeHtml(existingName)}</span>
                            </span>
                        </div>
                        <div class="loom-media-parameter-display__actions">
                            <a class="loom-media-parameter-display__preview-link ${hasUrl ? '' : 'd-none'}"
                               href="${hasUrl ? escapeHtml(compound.url) : '#'}"
                               target="_blank"
                               rel="noopener noreferrer"
                               data-media-parameter-preview-link>
                                Preview
                            </a>
                            ${disabled ? '' : `
                                <button type="button"
                                        class="loom-media-parameter-display__change"
                                        data-media-parameter-open>
                                    Change
                                </button>
                            `}
                        </div>
                        ${disabled ? '' : `
                            <button type="button"
                                    class="loom-media-parameter-clear"
                                    data-media-parameter-clear
                                    aria-label="Clear media">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        `}
                    </div>
                </div>
            </div>
            ${tipHtml}
        </div>
    `;
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
    colClass = null,
    controlClass = 'form-control',
}) {
    const resolvedValue = value !== undefined && value !== null ? value : (defaultValue ?? '');
    const requiredAttr = required ? ' required' : '';
    const disabledAttr = disabled ? ' disabled' : '';
    const tipHtml = parameterTipHtml(tip);
    const labelHtml = escapeHtml(label);
    const resolvedColClass = colClass ?? defaultColClassForType(type);

    if (type === 'textarea' || type === 'code') {
        const codeClass = type === 'code' ? ' font-monospace' : '';

        return `
            <div class="${resolvedColClass}">
                <label class="form-label">${labelHtml}</label>
                <textarea class="${controlClass}${codeClass}" name="${fieldName}" rows="${type === 'code' ? 4 : 3}"${requiredAttr}${disabledAttr}>${escapeHtml(resolvedValue)}</textarea>
                ${tipHtml}
            </div>
        `;
    }

    if (type === 'richtext') {
        const fieldId = richTextFieldId(fieldName);
        const textareaContent = String(resolvedValue).replace(/<\/textarea/gi, '&lt;/textarea');

        return `
            <div class="${resolvedColClass}">
                <label class="form-label" for="${fieldId}">${labelHtml}</label>
                <div class="loom-rich-text-field">
                    <textarea class="loom-rich-text-source" id="${fieldId}" name="${fieldName}" hidden${requiredAttr}${disabledAttr}>${textareaContent}</textarea>
                    <div class="loom-rich-text-editor"
                         data-rich-text-editor
                         data-target="${fieldId}"
                         ${disabled ? 'data-disabled="true"' : ''}></div>
                </div>
                ${tipHtml}
            </div>
        `;
    }

    if (type === 'checkbox') {
        const checked = resolvedValue === true || resolvedValue === '1' || resolvedValue === 1 || resolvedValue === 'on';

        return `
            <div class="${resolvedColClass}">
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
            <div class="${resolvedColClass}">
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
            <div class="${resolvedColClass}">
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

    if (type === 'media_selector' || type === 'media_attach' || type === 'media_finder') {
        return buildMediaParameterField({
            fieldName,
            label,
            type: type === 'media_finder' ? 'media_selector' : type,
            value: resolvedValue,
            tip,
            required,
            disabled,
            colClass,
            controlClass,
        });
    }

    if (type === 'url') {
        return buildUrlParameterFieldHtml({
            fieldName,
            label,
            value: resolvedValue,
            tip,
            disabled,
            colClass: resolvedColClass,
        });
    }

    const inputType = resolveNativeInputType(type);
    const fileValueAttr = type === 'file' ? '' : ` value="${escapeHtml(resolvedValue)}"`;

    return `
        <div class="${resolvedColClass}">
            <label class="form-label">${labelHtml}</label>
            <input type="${inputType}" class="${controlClass}" name="${fieldName}"${fileValueAttr}${requiredAttr}${disabledAttr}>
            ${tipHtml}
        </div>
    `;
}
