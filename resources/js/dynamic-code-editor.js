import * as bootstrap from 'bootstrap';
import { confirmAction } from './admin-notifications';
import { EditorView, Decoration, keymap } from '@codemirror/view';
import { EditorState, StateField, Prec } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';

const DATA_PLACEHOLDER_PATTERN = /\{\{\s*\$(\w+)\[['"]([^'"]+)['"]\]\s*\}\}/g;
const LEGACY_PLACEHOLDER_PATTERN = /\{\{\s*([a-zA-Z][a-zA-Z0-9_]*)\.([a-z][a-z0-9_]*)\s*\}\}/g;
const FOREACH_START_PATTERN = /@foreach\s*\(\s*\$(\w+)\[['"](\w+)['"]\]\s*\?\?\s*\[\]\s+as\s+\$(\w+)\s*\)/g;
const NAME_PATTERN = /^[a-z][a-z0-9_]*$/;

const FALLBACK_PARAMETER_TYPE_LABELS = {
    text: 'Text',
};

function parseParameterTypeLabels(raw) {
    if (! raw) {
        return { ...FALLBACK_PARAMETER_TYPE_LABELS };
    }

    try {
        const parsed = JSON.parse(raw);

        return parsed && typeof parsed === 'object' ? parsed : { ...FALLBACK_PARAMETER_TYPE_LABELS };
    } catch {
        return { ...FALLBACK_PARAMETER_TYPE_LABELS };
    }
}

function getCodeLanguage(language) {
    if (language === 'javascript' || language === 'js') {
        return javascript();
    }

    return html();
}

function slugifyName(label) {
    return label
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .replace(/^(\d)/, '_$1');
}

function singularizeName(name) {
    if (name.endsWith('s') && name.length > 1) {
        return name.slice(0, -1);
    }

    if (name.endsWith('ies') && name.length > 3) {
        return name.slice(0, -3) + 'y';
    }

    return `${name}_item`;
}

function buildPlaceholderToken(name, prefix = 'blockData') {
    return `{{ $${prefix}['${name}'] }}`;
}

function buildLoopPlaceholderToken(name, itemName) {
    return buildPlaceholderToken(name, itemName);
}

function getLineIndentAt(doc, pos) {
    const line = doc.lineAt(pos);
    const match = line.text.match(/^(\s*)/);

    return match ? match[1] : '';
}

function buildLoopWrapper(loopName, itemName, content, dataPrefix = 'blockData', baseIndent = '') {
    const open = `${baseIndent}@foreach ($${dataPrefix}['${loopName}'] ?? [] as $${itemName})`;
    const close = `${baseIndent}@endforeach`;

    return `${open}\n${content}\n${close}`;
}

function parseStoredValue(raw) {
    if (! raw) {
        return { template: '', parameters: [] };
    }

    try {
        const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;

        if (parsed && typeof parsed === 'object' && 'template' in parsed) {
            return {
                template: typeof parsed.template === 'string' ? parsed.template : '',
                parameters: Array.isArray(parsed.parameters) ? parsed.parameters : [],
            };
        }
    } catch {
        //
    }

    return {
        template: typeof raw === 'string' ? raw : '',
        parameters: [],
    };
}

function parseLoops(text) {
    const tokens = [];
    let match;

    const startRe = /@foreach\s*\(\s*\$(\w+)\[['"](\w+)['"]\]\s*\?\?\s*\[\]\s+as\s+\$(\w+)\s*\)/g;

    while ((match = startRe.exec(text)) !== null) {
        tokens.push({
            type: 'start',
            index: match.index,
            end: match.index + match[0].length,
            loopName: match[2],
            itemName: match[3],
        });
    }

    const endRe = /@endforeach/g;

    while ((match = endRe.exec(text)) !== null) {
        tokens.push({
            type: 'end',
            index: match.index,
            end: match.index + match[0].length,
        });
    }

    tokens.sort((a, b) => a.index - b.index);

    const stack = [];
    const loops = [];

    for (const token of tokens) {
        if (token.type === 'start') {
            stack.push(token);
        } else if (token.type === 'end' && stack.length > 0) {
            const start = stack.pop();
            loops.push({
                loopName: start.loopName,
                itemName: start.itemName,
                from: start.index,
                to: token.end,
                contentFrom: start.end,
                contentTo: token.index,
            });
        }
    }

    return loops;
}

function findEnclosingLoop(doc, from, to) {
    const loops = parseLoops(doc.toString());
    const mid = (from + to) / 2;
    let innermost = null;

    for (const loop of loops) {
        if (loop.contentFrom <= mid && loop.contentTo >= mid) {
            if (! innermost || (loop.contentTo - loop.contentFrom) < (innermost.contentTo - innermost.contentFrom)) {
                innermost = loop;
            }
        }
    }

    return innermost;
}

function buildEditorDecorations(doc) {
    const decorations = [];
    const text = doc.toString();

    let match;
    const dataPlaceholderRe = new RegExp(DATA_PLACEHOLDER_PATTERN.source, 'g');
    const legacyPlaceholderRe = new RegExp(LEGACY_PLACEHOLDER_PATTERN.source, 'g');

    while ((match = dataPlaceholderRe.exec(text)) !== null) {
        decorations.push(
            Decoration.mark({ class: 'cm-dynamic-placeholder' }).range(
                match.index,
                match.index + match[0].length
            )
        );
    }

    while ((match = legacyPlaceholderRe.exec(text)) !== null) {
        decorations.push(
            Decoration.mark({ class: 'cm-dynamic-placeholder' }).range(
                match.index,
                match.index + match[0].length
            )
        );
    }

    const loopStartRe = /@foreach\s*\([^)]+\)/g;

    while ((match = loopStartRe.exec(text)) !== null) {
        decorations.push(
            Decoration.line({ class: 'cm-blade-foreach-marker' }).range(match.index)
        );
    }

    const loopEndRe = /@endforeach/g;

    while ((match = loopEndRe.exec(text)) !== null) {
        decorations.push(
            Decoration.line({ class: 'cm-blade-foreach-marker' }).range(match.index)
        );
    }

    return Decoration.set(decorations, true);
}

const placeholderField = StateField.define({
    create(state) {
        return buildEditorDecorations(state.doc);
    },
    update(decorations, transaction) {
        if (transaction.docChanged) {
            return buildEditorDecorations(transaction.state.doc);
        }

        return decorations;
    },
    provide: (field) => EditorView.decorations.from(field),
});

function rangesOverlap(fromA, toA, fromB, toB) {
    return fromA < toB && toA > fromB;
}

function selectionOverlapsPlaceholder(doc, from, to) {
    const text = doc.toString();
    const patterns = [
        new RegExp(DATA_PLACEHOLDER_PATTERN.source, 'g'),
        new RegExp(LEGACY_PLACEHOLDER_PATTERN.source, 'g'),
    ];

    for (const pattern of patterns) {
        let match;

        while ((match = pattern.exec(text)) !== null) {
            const matchFrom = match.index;
            const matchTo = match.index + match[0].length;

            if (rangesOverlap(from, to, matchFrom, matchTo)) {
                return true;
            }
        }
    }

    return false;
}

function findPlaceholderRanges(doc, name, prefix = 'blockData') {
    const ranges = [];
    const text = doc.toString();
    const tokens = [
        buildPlaceholderToken(name, prefix),
        `{{ ${prefix}.${name} }}`,
    ];

    for (const token of tokens) {
        let index = 0;

        while (index < text.length) {
            const found = text.indexOf(token, index);

            if (found === -1) {
                break;
            }

            ranges.push({ from: found, to: found + token.length });
            index = found + token.length;
        }
    }

    return ranges;
}

function findLoopPlaceholderRanges(doc, name, itemName) {
    return findPlaceholderRanges(doc, name, itemName);
}

function createDynamicCodeEditor(root) {
    if (root.dataset.initialized === 'true') {
        return;
    }

    const inputId = root.dataset.inputId;
    const modalId = root.dataset.modalId;
    const loopModalId = root.dataset.loopModalId;
    const menuId = root.dataset.menuId;
    const language = root.dataset.language || 'html';
    const placeholderPrefix = root.dataset.placeholderPrefix || 'blockData';
    const parameterTypeLabels = parseParameterTypeLabels(root.dataset.parameterTypes);
    const isDisabled = root.dataset.disabled === 'true';
    const isReadonly = root.dataset.readonly === 'true';

    const hiddenInput = inputId ? document.getElementById(inputId) : null;
    const mount = root.querySelector('[data-dynamic-code-mount]');
    const parametersList = root.querySelector('[data-dynamic-code-parameters-list]');
    const parametersEmpty = root.querySelector('[data-dynamic-code-parameters-empty]');
    const contextMenu = menuId ? document.getElementById(menuId) : null;
    const modalEl = modalId ? document.getElementById(modalId) : null;
    const loopModalEl = loopModalId ? document.getElementById(loopModalId) : null;

    if (! hiddenInput || ! mount || ! parametersList || ! modalEl || ! loopModalEl) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const loopModal = bootstrap.Modal.getOrCreateInstance(loopModalEl);
    const modalTitle = modalEl.querySelector('[data-dynamic-code-modal-title]');
    const modalError = modalEl.querySelector('[data-dynamic-code-modal-error]');
    const modalSubmit = modalEl.querySelector('[data-dynamic-code-modal-submit]');
    const paramTypeInput = modalEl.querySelector('[data-dynamic-code-param-type]');
    const paramTypeWrap = paramTypeInput?.closest('.mb-3');
    const paramLabelInput = modalEl.querySelector('[data-dynamic-code-param-label]');
    const paramNameInput = modalEl.querySelector('[data-dynamic-code-param-name]');
    const paramTipInput = modalEl.querySelector('[data-dynamic-code-param-tip]');
    const makeDynamicBtn = contextMenu?.querySelector('[data-dynamic-code-make-dynamic]');
    const makeLoopBtn = contextMenu?.querySelector('[data-dynamic-code-make-loop]');

    const loopModalTitle = loopModalEl.querySelector('[data-dynamic-code-loop-modal-title]');
    const loopModalError = loopModalEl.querySelector('[data-dynamic-code-loop-modal-error]');
    const loopModalSubmit = loopModalEl.querySelector('[data-dynamic-code-loop-modal-submit]');
    const loopLabelInput = loopModalEl.querySelector('[data-dynamic-code-loop-label]');
    const loopNameInput = loopModalEl.querySelector('[data-dynamic-code-loop-name]');
    const loopItemInput = loopModalEl.querySelector('[data-dynamic-code-loop-item]');
    const loopTipInput = loopModalEl.querySelector('[data-dynamic-code-loop-tip]');

    const initial = parseStoredValue(hiddenInput.value);
    let parameters = [...initial.parameters];
    let pendingSelection = null;
    let stashedContextSelection = null;
    let editingParameterName = null;
    let editingLoopName = null;
    let editingSubField = null;
    let activeLoopContext = null;

    const shortcutHandlers = {
        openDynamicModal: null,
    };

    const editor = new EditorView({
        state: EditorState.create({
            doc: initial.template,
            extensions: [
                basicSetup,
                getCodeLanguage(language),
                placeholderField,
                Prec.highest(keymap.of([
                    {
                        key: 'Mod-a',
                        run(view) {
                            if (isDisabled || isReadonly) {
                                return false;
                            }

                            const selection = view.state.selection.main;

                            if (selection.empty) {
                                return false;
                            }

                            if (selectionOverlapsPlaceholder(view.state.doc, selection.from, selection.to)) {
                                return false;
                            }

                            shortcutHandlers.openDynamicModal?.(view);

                            return true;
                        },
                    },
                ])),
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        syncHiddenInput();
                    }

                    if (update.selectionSet) {
                        const selection = update.state.selection.main;

                        if (! selection.empty) {
                            stashedContextSelection = {
                                from: selection.from,
                                to: selection.to,
                            };
                        }
                    }
                }),
                EditorView.editable.of(!isDisabled && !isReadonly),
                EditorView.domEventHandlers({
                    mousedown(event, view) {
                        if (isDisabled || isReadonly || event.button !== 2) {
                            return false;
                        }

                        const selection = view.state.selection.main;

                        if (! selection.empty) {
                            stashedContextSelection = {
                                from: selection.from,
                                to: selection.to,
                            };
                        }

                        return false;
                    },
                    contextmenu(event, view) {
                        if (isDisabled || isReadonly) {
                            return false;
                        }

                        const selection = getActiveSelection(view);

                        if (selection.empty) {
                            hideContextMenu();
                            return false;
                        }

                        if (selectionOverlapsPlaceholder(view.state.doc, selection.from, selection.to)) {
                            hideContextMenu();
                            return false;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                        showContextMenu(event.clientX, event.clientY, selection);
                        return true;
                    },
                }),
                EditorView.theme({
                    '&': {
                        minHeight: '280px',
                        fontSize: '0.875rem',
                    },
                    '.cm-scroller': {
                        fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
                    },
                    '.cm-blade-foreach-marker': {
                        backgroundColor: 'rgba(99, 102, 241, 0.12)',
                    },
                }),
                oneDark,
            ],
        }),
        parent: mount,
    });

    mount.editorView = editor;
    root.dataset.initialized = 'true';

    function syncHiddenInput() {
        hiddenInput.value = JSON.stringify({
            template: editor.state.doc.toString(),
            parameters,
        });
    }

    function getActiveSelection(view) {
        const current = view.state.selection.main;

        if (! current.empty) {
            return current;
        }

        if (stashedContextSelection) {
            return {
                from: stashedContextSelection.from,
                to: stashedContextSelection.to,
                empty: false,
            };
        }

        return current;
    }

    function hideContextMenu(clearSelection = true) {
        if (! contextMenu) {
            if (clearSelection) {
                pendingSelection = null;
                stashedContextSelection = null;
                activeLoopContext = null;
            }

            return;
        }

        contextMenu.classList.add('d-none');

        if (clearSelection) {
            pendingSelection = null;
            stashedContextSelection = null;
            activeLoopContext = null;
        }
    }

    function stageSelection(selection) {
        pendingSelection = {
            from: selection.from,
            to: selection.to,
            text: editor.state.doc.sliceString(selection.from, selection.to),
        };

        stashedContextSelection = {
            from: selection.from,
            to: selection.to,
        };

        activeLoopContext = findEnclosingLoop(editor.state.doc, selection.from, selection.to);
    }

    function showContextMenu(x, y, selection) {
        if (! contextMenu) {
            return;
        }

        stageSelection(selection);

        contextMenu.style.left = `${x}px`;
        contextMenu.style.top = `${y}px`;
        contextMenu.classList.remove('d-none');
    }

    function clearModalError(target = modalError) {
        if (! target) {
            return;
        }

        target.textContent = '';
        target.classList.add('d-none');
    }

    function showModalError(message, target = modalError) {
        if (! target) {
            return;
        }

        target.textContent = message;
        target.classList.remove('d-none');
    }

    function getRepeaterParameter(loopName) {
        return parameters.find((parameter) => parameter.name === loopName && parameter.type === 'repeater') ?? null;
    }

    function openCreateModal() {
        editingParameterName = null;
        editingSubField = null;
        clearModalError();

        if (! pendingSelection) {
            const selection = getActiveSelection(editor);

            if (! selection.empty) {
                pendingSelection = {
                    from: selection.from,
                    to: selection.to,
                    text: editor.state.doc.sliceString(selection.from, selection.to),
                };
            }
        }

        activeLoopContext = pendingSelection
            ? findEnclosingLoop(editor.state.doc, pendingSelection.from, pendingSelection.to)
            : null;

        if (modalTitle) {
            modalTitle.textContent = activeLoopContext ? 'Make dynamic (inside loop)' : 'Make dynamic';
        }

        if (modalSubmit) {
            modalSubmit.textContent = 'Add parameter';
        }

        if (paramTypeWrap) {
            paramTypeWrap.classList.remove('d-none');
        }

        if (paramTypeInput) {
            paramTypeInput.value = 'text';
            paramTypeInput.disabled = false;
        }

        if (paramLabelInput) {
            paramLabelInput.value = '';
        }

        if (paramNameInput) {
            paramNameInput.value = '';
            paramNameInput.readOnly = false;
        }

        if (paramTipInput) {
            paramTipInput.value = '';
        }

        modal.show();

        modalEl.addEventListener('shown.bs.modal', () => {
            paramLabelInput?.focus();
        }, { once: true });
    }

    shortcutHandlers.openDynamicModal = (view) => {
        const selection = view.state.selection.main;

        stageSelection(selection);
        hideContextMenu(false);
        openCreateModal();
    };

    function openEditModal(parameter, subField = null) {
        editingSubField = subField
            ? { loopName: parameter.name, fieldName: subField.name }
            : null;
        editingParameterName = subField ? null : parameter.name;
        editingLoopName = null;
        clearModalError();
        hideContextMenu();

        if (modalTitle) {
            modalTitle.textContent = subField ? 'Edit loop field' : 'Edit parameter';
        }

        if (modalSubmit) {
            modalSubmit.textContent = 'Save changes';
        }

        if (paramTypeWrap) {
            paramTypeWrap.classList.remove('d-none');
        }

        const target = editingSubField
            ? (getRepeaterParameter(editingSubField.loopName)?.fields ?? []).find((field) => field.name === editingSubField.fieldName)
            : parameter;

        if (! target) {
            return;
        }

        if (paramTypeInput) {
            paramTypeInput.value = target.type || 'text';
        }

        if (paramLabelInput) {
            paramLabelInput.value = target.label || '';
        }

        if (paramNameInput) {
            paramNameInput.value = target.name;
            paramNameInput.readOnly = true;
        }

        if (paramTipInput) {
            paramTipInput.value = target.tip || '';
        }

        modal.show();
        paramLabelInput?.focus();
    }

    function openEditLoopModal(parameter) {
        editingLoopName = parameter.name;
        editingParameterName = null;
        editingSubField = null;
        clearModalError(loopModalError);
        hideContextMenu();

        if (loopModalTitle) {
            loopModalTitle.textContent = 'Edit loop';
        }

        if (loopModalSubmit) {
            loopModalSubmit.textContent = 'Save changes';
        }

        if (loopLabelInput) {
            loopLabelInput.value = parameter.label || '';
        }

        if (loopNameInput) {
            loopNameInput.value = parameter.name;
            loopNameInput.readOnly = true;
        }

        if (loopItemInput) {
            loopItemInput.value = parameter.item || singularizeName(parameter.name);
            loopItemInput.readOnly = true;
        }

        if (loopTipInput) {
            loopTipInput.value = parameter.tip || '';
        }

        loopModal.show();
        loopLabelInput?.focus();
    }

    function openCreateLoopModal() {
        editingLoopName = null;
        clearModalError(loopModalError);

        if (! pendingSelection) {
            const selection = getActiveSelection(editor);

            if (! selection.empty) {
                pendingSelection = {
                    from: selection.from,
                    to: selection.to,
                    text: editor.state.doc.sliceString(selection.from, selection.to),
                };
            }
        }

        if (loopModalTitle) {
            loopModalTitle.textContent = 'Make loop';
        }

        if (loopModalSubmit) {
            loopModalSubmit.textContent = 'Add loop';
        }

        if (loopLabelInput) {
            loopLabelInput.value = '';
        }

        if (loopNameInput) {
            loopNameInput.value = '';
            loopNameInput.readOnly = false;
        }

        if (loopItemInput) {
            loopItemInput.value = '';
            loopItemInput.readOnly = false;
        }

        if (loopTipInput) {
            loopTipInput.value = '';
        }

        loopModal.show();

        loopModalEl.addEventListener('shown.bs.modal', () => {
            loopLabelInput?.focus();
        }, { once: true });
    }

    function validateParameterInput(name, label, type, loopName = null) {
        if (! label.trim()) {
            return 'Label is required.';
        }

        if (! NAME_PATTERN.test(name)) {
            return 'Field name must start with a letter and contain only lowercase letters, numbers, and underscores.';
        }

        if (! parameterTypeLabels[type] && type !== 'repeater') {
            return 'Invalid field type.';
        }

        if (loopName) {
            const repeater = getRepeaterParameter(loopName);

            if (! repeater) {
                return 'Loop not found.';
            }

            const fields = repeater.fields ?? [];

            if (! editingSubField && fields.some((field) => field.name === name)) {
                return 'A field with this name already exists in the loop.';
            }

            return null;
        }

        if (! editingParameterName && parameters.some((parameter) => parameter.name === name)) {
            return 'A parameter with this field name already exists.';
        }

        return null;
    }

    function validateLoopInput(name, label, item) {
        if (! label.trim()) {
            return 'Label is required.';
        }

        if (! NAME_PATTERN.test(name)) {
            return 'Loop name must start with a letter and contain only lowercase letters, numbers, and underscores.';
        }

        if (! NAME_PATTERN.test(item)) {
            return 'Item variable must start with a letter and contain only lowercase letters, numbers, and underscores.';
        }

        if (! editingLoopName && parameters.some((parameter) => parameter.name === name)) {
            return 'A parameter with this loop name already exists.';
        }

        return null;
    }

    function renderSubFieldItem(loopParameter, field) {
        const item = document.createElement('div');
        item.className = 'dynamic-code-parameters__item dynamic-code-parameters__item--nested';
        item.dataset.dynamicCodeSubFieldItem = `${loopParameter.name}:${field.name}`;

        const typeLabel = parameterTypeLabels[field.type] || (field.type === 'repeater' ? 'Loop' : field.type);
        const tipHtml = field.tip
            ? `<div class="dynamic-code-parameters__item-tip">${escapeHtml(field.tip)}</div>`
            : '';

        item.innerHTML = `
            <div class="dynamic-code-parameters__item-main">
                <div class="dynamic-code-parameters__item-label">${escapeHtml(field.label)}</div>
                <div class="dynamic-code-parameters__item-meta">
                    <code class="dynamic-code-parameters__item-name">${escapeHtml(`$${loopParameter.item}['${field.name}']`)}</code>
                    <span class="badge text-bg-secondary">${escapeHtml(typeLabel)}</span>
                </div>
                ${tipHtml}
            </div>
            <div class="dynamic-code-parameters__item-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-dynamic-code-edit-subfield title="Edit field">Edit</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-dynamic-code-remove-subfield title="Remove field">Remove</button>
            </div>
        `;

        item.querySelector('[data-dynamic-code-edit-subfield]')?.addEventListener('click', () => {
            openEditModal(loopParameter, field);
        });

        item.querySelector('[data-dynamic-code-remove-subfield]')?.addEventListener('click', () => {
            removeSubField(loopParameter.name, field.name);
        });

        return item;
    }

    function renderParametersPanel() {
        parametersList.querySelectorAll('[data-dynamic-code-parameter-item], .dynamic-code-parameters__nested').forEach((item) => item.remove());

        if (parameters.length === 0) {
            parametersEmpty?.classList.remove('d-none');
            return;
        }

        parametersEmpty?.classList.add('d-none');

        parameters.forEach((parameter) => {
            const item = document.createElement('div');
            item.className = 'dynamic-code-parameters__item';
            item.dataset.dynamicCodeParameterItem = parameter.name;

            const typeLabel = parameterTypeLabels[parameter.type] || (parameter.type === 'repeater' ? 'Loop' : parameter.type);
            const tipHtml = parameter.tip
                ? `<div class="dynamic-code-parameters__item-tip">${escapeHtml(parameter.tip)}</div>`
                : '';

            item.innerHTML = `
                <div class="dynamic-code-parameters__item-main">
                    <div class="dynamic-code-parameters__item-label">${escapeHtml(parameter.label)}</div>
                    <div class="dynamic-code-parameters__item-meta">
                        <code class="dynamic-code-parameters__item-name">${escapeHtml(`$${placeholderPrefix}['${parameter.name}']`)}</code>
                        <span class="badge text-bg-secondary">${escapeHtml(typeLabel)}</span>
                    </div>
                    ${tipHtml}
                </div>
                <div class="dynamic-code-parameters__item-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dynamic-code-edit title="Edit">Edit</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-dynamic-code-remove title="Remove">Remove</button>
                </div>
            `;

            if (parameter.type === 'repeater') {
                item.querySelector('[data-dynamic-code-edit]')?.addEventListener('click', () => {
                    openEditLoopModal(parameter);
                });

                item.querySelector('[data-dynamic-code-remove]')?.addEventListener('click', () => {
                    removeLoop(parameter.name);
                });

                const nested = document.createElement('div');
                nested.className = 'dynamic-code-parameters__nested';

                (parameter.fields ?? []).forEach((field) => {
                    nested.appendChild(renderSubFieldItem(parameter, field));
                });

                parametersList.appendChild(item);
                parametersList.appendChild(nested);

                return;
            }

            item.querySelector('[data-dynamic-code-edit]')?.addEventListener('click', () => {
                openEditModal(parameter);
            });

            item.querySelector('[data-dynamic-code-remove]')?.addEventListener('click', () => {
                removeParameter(parameter.name);
            });

            parametersList.appendChild(item);
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function applyParameterMeta(parameter, label, type, tip) {
        const updated = {
            ...parameter,
            label,
            type,
        };

        if (tip) {
            updated.tip = tip;
        } else {
            delete updated.tip;
        }

        return updated;
    }

    function addParameterFromModal() {
        const type = paramTypeInput?.value || 'text';
        const label = paramLabelInput?.value?.trim() || '';
        const name = paramNameInput?.value?.trim() || '';
        const tip = paramTipInput?.value?.trim() || '';
        const loopName = activeLoopContext?.loopName ?? editingSubField?.loopName ?? null;

        const error = validateParameterInput(name, label, type, loopName);

        if (error) {
            showModalError(error);
            return;
        }

        if (editingSubField) {
            parameters = parameters.map((parameter) => {
                if (parameter.name !== editingSubField.loopName || parameter.type !== 'repeater') {
                    return parameter;
                }

                return {
                    ...parameter,
                    fields: (parameter.fields ?? []).map((field) => {
                        if (field.name !== editingSubField.fieldName) {
                            return field;
                        }

                        return applyParameterMeta(field, label, type, tip);
                    }),
                };
            });
        } else if (editingParameterName) {
            parameters = parameters.map((parameter) => {
                if (parameter.name !== editingParameterName) {
                    return parameter;
                }

                return applyParameterMeta(parameter, label, type, tip);
            });
        } else if (loopName) {
            if (! pendingSelection) {
                showModalError('No text selected in the editor.');
                return;
            }

            const repeater = getRepeaterParameter(loopName);
            const itemName = repeater?.item || activeLoopContext?.itemName || singularizeName(loopName);
            const defaultValue = pendingSelection.text;
            const newField = {
                name,
                label,
                type,
                default: defaultValue,
            };

            if (tip) {
                newField.tip = tip;
            }

            parameters = parameters.map((parameter) => {
                if (parameter.name !== loopName || parameter.type !== 'repeater') {
                    return parameter;
                }

                return {
                    ...parameter,
                    fields: [...(parameter.fields ?? []), newField],
                };
            });

            editor.dispatch({
                changes: {
                    from: pendingSelection.from,
                    to: pendingSelection.to,
                    insert: buildLoopPlaceholderToken(name, itemName),
                },
            });
        } else {
            if (! pendingSelection) {
                showModalError('No text selected in the editor.');
                return;
            }

            const defaultValue = pendingSelection.text;
            const newParameter = {
                name,
                label,
                type,
                default: defaultValue,
            };

            if (tip) {
                newParameter.tip = tip;
            }

            parameters = [
                ...parameters,
                newParameter,
            ];

            editor.dispatch({
                changes: {
                    from: pendingSelection.from,
                    to: pendingSelection.to,
                    insert: buildPlaceholderToken(name, placeholderPrefix),
                },
            });
        }

        pendingSelection = null;
        stashedContextSelection = null;
        activeLoopContext = null;
        editingSubField = null;
        hideContextMenu(false);
        modal.hide();
        renderParametersPanel();
        syncHiddenInput();
    }

    function addLoopFromModal() {
        const label = loopLabelInput?.value?.trim() || '';
        const name = loopNameInput?.value?.trim() || '';
        const item = loopItemInput?.value?.trim() || '';
        const tip = loopTipInput?.value?.trim() || '';

        const error = validateLoopInput(name, label, item);

        if (error) {
            showModalError(error, loopModalError);
            return;
        }

        if (editingLoopName) {
            parameters = parameters.map((parameter) => {
                if (parameter.name !== editingLoopName || parameter.type !== 'repeater') {
                    return parameter;
                }

                const updated = {
                    ...parameter,
                    label,
                };

                if (tip) {
                    updated.tip = tip;
                } else {
                    delete updated.tip;
                }

                return updated;
            });
        } else {
            if (! pendingSelection) {
                showModalError('No text selected in the editor.', loopModalError);
                return;
            }

            const content = pendingSelection.text;
            const baseIndent = getLineIndentAt(editor.state.doc, pendingSelection.from);
            const wrapped = buildLoopWrapper(name, item, content, placeholderPrefix, baseIndent);

            parameters = [
                ...parameters,
                {
                    name,
                    label,
                    type: 'repeater',
                    item,
                    fields: [],
                    ...(tip ? { tip } : {}),
                },
            ];

            editor.dispatch({
                changes: {
                    from: pendingSelection.from,
                    to: pendingSelection.to,
                    insert: wrapped,
                },
            });
        }

        pendingSelection = null;
        stashedContextSelection = null;
        hideContextMenu(false);
        loopModal.hide();
        renderParametersPanel();
        syncHiddenInput();
    }

    async function removeParameter(name) {
        const parameter = parameters.find((item) => item.name === name);

        if (! parameter || parameter.type === 'repeater') {
            return;
        }

        const replacement = parameter.default ?? '';
        const ranges = findPlaceholderRanges(editor.state.doc, name, placeholderPrefix);

        if (ranges.length === 0) {
            parameters = parameters.filter((item) => item.name !== name);
            renderParametersPanel();
            syncHiddenInput();
            return;
        }

        const confirmed = await confirmAction({
            title: 'Remove parameter',
            message: `Remove "${parameter.label}" and replace ${ranges.length} placeholder(s) with the original default text?`,
            confirmLabel: 'Remove',
            danger: true,
        });

        if (! confirmed) {
            return;
        }

        editor.dispatch({
            changes: ranges
                .slice()
                .reverse()
                .map((range) => ({
                    from: range.from,
                    to: range.to,
                    insert: replacement,
                })),
        });

        parameters = parameters.filter((item) => item.name !== name);
        renderParametersPanel();
        syncHiddenInput();
    }

    async function removeSubField(loopName, fieldName) {
        const repeater = getRepeaterParameter(loopName);
        const field = repeater?.fields?.find((item) => item.name === fieldName);

        if (! repeater || ! field) {
            return;
        }

        const itemName = repeater.item || singularizeName(loopName);
        const replacement = field.default ?? '';
        const ranges = findLoopPlaceholderRanges(editor.state.doc, fieldName, itemName);

        if (ranges.length > 0) {
            const confirmed = await confirmAction({
                title: 'Remove loop field',
                message: `Remove "${field.label}" and replace ${ranges.length} placeholder(s) with the original default text?`,
                confirmLabel: 'Remove',
                danger: true,
            });

            if (! confirmed) {
                return;
            }

            editor.dispatch({
                changes: ranges
                    .slice()
                    .reverse()
                    .map((range) => ({
                        from: range.from,
                        to: range.to,
                        insert: replacement,
                    })),
            });
        }

        parameters = parameters.map((parameter) => {
            if (parameter.name !== loopName || parameter.type !== 'repeater') {
                return parameter;
            }

            return {
                ...parameter,
                fields: (parameter.fields ?? []).filter((item) => item.name !== fieldName),
            };
        });

        renderParametersPanel();
        syncHiddenInput();
    }

    async function removeLoop(loopName) {
        const repeater = getRepeaterParameter(loopName);

        if (! repeater) {
            return;
        }

        const confirmed = await confirmAction({
            title: 'Remove loop',
            message: `Remove loop "${repeater.label}" and unwrap its template content?`,
            confirmLabel: 'Remove',
            danger: true,
        });

        if (! confirmed) {
            return;
        }

        const loops = parseLoops(editor.state.doc.toString());
        const loop = loops.find((item) => item.loopName === loopName);

        if (loop) {
            const innerContent = editor.state.doc.sliceString(loop.contentFrom, loop.contentTo);
            editor.dispatch({
                changes: {
                    from: loop.from,
                    to: loop.to,
                    insert: innerContent,
                },
            });
        }

        parameters = parameters.filter((parameter) => parameter.name !== loopName);
        renderParametersPanel();
        syncHiddenInput();
    }

    makeDynamicBtn?.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    makeDynamicBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        hideContextMenu(false);
        openCreateModal();
    });

    makeLoopBtn?.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    makeLoopBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        hideContextMenu(false);
        openCreateLoopModal();
    });

    contextMenu?.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    modalSubmit?.addEventListener('click', addParameterFromModal);
    loopModalSubmit?.addEventListener('click', addLoopFromModal);

    paramLabelInput?.addEventListener('input', () => {
        if (! paramNameInput || paramNameInput.readOnly || editingParameterName || editingSubField) {
            return;
        }

        paramNameInput.value = slugifyName(paramLabelInput.value);
    });

    loopLabelInput?.addEventListener('input', () => {
        if (! loopNameInput || loopNameInput.readOnly || editingLoopName) {
            return;
        }

        const name = slugifyName(loopLabelInput.value);

        loopNameInput.value = name;

        if (loopItemInput && ! loopItemInput.readOnly) {
            loopItemInput.value = singularizeName(name);
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        clearModalError();
        editingParameterName = null;
        editingSubField = null;

        if (! loopModalEl.classList.contains('show')) {
            pendingSelection = null;
            stashedContextSelection = null;
            activeLoopContext = null;
        }

        if (paramNameInput) {
            paramNameInput.readOnly = false;
        }
    });

    loopModalEl.addEventListener('hidden.bs.modal', () => {
        clearModalError(loopModalError);
        editingLoopName = null;
        pendingSelection = null;
        stashedContextSelection = null;

        if (loopNameInput) {
            loopNameInput.readOnly = false;
        }

        if (loopItemInput) {
            loopItemInput.readOnly = false;
        }
    });

    document.addEventListener(
        'click',
        (event) => {
            if (! contextMenu || contextMenu.classList.contains('d-none')) {
                return;
            }

            if (contextMenu.contains(event.target)) {
                return;
            }

            hideContextMenu();
        },
        true
    );

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideContextMenu();
        }
    });

    renderParametersPanel();
    syncHiddenInput();

    root.addEventListener('loom:sync-code', () => {
        syncHiddenInput();
    });
}

export function syncDynamicCodeEditors() {
    document.querySelectorAll('[data-dynamic-code-editor][data-initialized="true"]').forEach((root) => {
        root.dispatchEvent(new CustomEvent('loom:sync-code'));
    });
}

export function initDynamicCodeEditors() {
    document.querySelectorAll('[data-dynamic-code-editor]').forEach(createDynamicCodeEditor);
}
