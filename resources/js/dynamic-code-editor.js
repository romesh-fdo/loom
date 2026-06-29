import * as bootstrap from 'bootstrap';
import { confirmAction } from './admin-notifications';
import { EditorView, Decoration } from '@codemirror/view';
import { EditorState, StateField } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';

const PLACEHOLDER_PATTERN = /\{\{\s*blockData\.([a-z][a-z0-9_]*)\s*\}\}/g;
const NAME_PATTERN = /^[a-z][a-z0-9_]*$/;

const PARAMETER_TYPE_LABELS = {
    text: 'Text',
    textarea: 'Textarea',
    number: 'Number',
    email: 'Email',
    select: 'Select',
    checkbox: 'Checkbox',
    color: 'Color',
};

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

function buildPlaceholderToken(name) {
    return `{{ blockData.${name} }}`;
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

function buildPlaceholderDecorations(doc) {
    const decorations = [];
    const text = doc.toString();
    const pattern = new RegExp(PLACEHOLDER_PATTERN.source, 'g');
    let match;

    while ((match = pattern.exec(text)) !== null) {
        decorations.push(
            Decoration.mark({ class: 'cm-dynamic-placeholder' }).range(
                match.index,
                match.index + match[0].length
            )
        );
    }

    return Decoration.set(decorations, true);
}

const placeholderField = StateField.define({
    create(state) {
        return buildPlaceholderDecorations(state.doc);
    },
    update(decorations, transaction) {
        if (transaction.docChanged) {
            return buildPlaceholderDecorations(transaction.state.doc);
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
    const pattern = new RegExp(PLACEHOLDER_PATTERN.source, 'g');
    let match;

    while ((match = pattern.exec(text)) !== null) {
        const matchFrom = match.index;
        const matchTo = match.index + match[0].length;

        if (rangesOverlap(from, to, matchFrom, matchTo)) {
            return true;
        }
    }

    return false;
}

function findPlaceholderRanges(doc, name) {
    const ranges = [];
    const text = doc.toString();
    const token = buildPlaceholderToken(name);
    let index = 0;

    while (index < text.length) {
        const found = text.indexOf(token, index);

        if (found === -1) {
            break;
        }

        ranges.push({ from: found, to: found + token.length });
        index = found + token.length;
    }

    return ranges;
}

function createDynamicCodeEditor(root) {
    if (root.dataset.initialized === 'true') {
        return;
    }

    const inputId = root.dataset.inputId;
    const modalId = root.dataset.modalId;
    const menuId = root.dataset.menuId;
    const language = root.dataset.language || 'html';
    const isDisabled = root.dataset.disabled === 'true';
    const isReadonly = root.dataset.readonly === 'true';

    const hiddenInput = inputId ? document.getElementById(inputId) : null;
    const mount = root.querySelector('[data-dynamic-code-mount]');
    const parametersList = root.querySelector('[data-dynamic-code-parameters-list]');
    const parametersEmpty = root.querySelector('[data-dynamic-code-parameters-empty]');
    const contextMenu = menuId ? document.getElementById(menuId) : null;
    const modalEl = modalId ? document.getElementById(modalId) : null;

    if (! hiddenInput || ! mount || ! parametersList || ! modalEl) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const modalTitle = modalEl.querySelector('[data-dynamic-code-modal-title]');
    const modalError = modalEl.querySelector('[data-dynamic-code-modal-error]');
    const modalSubmit = modalEl.querySelector('[data-dynamic-code-modal-submit]');
    const paramTypeInput = modalEl.querySelector('[data-dynamic-code-param-type]');
    const paramLabelInput = modalEl.querySelector('[data-dynamic-code-param-label]');
    const paramNameInput = modalEl.querySelector('[data-dynamic-code-param-name]');
    const makeDynamicBtn = contextMenu?.querySelector('[data-dynamic-code-make-dynamic]');

    const initial = parseStoredValue(hiddenInput.value);
    let parameters = [...initial.parameters];
    let pendingSelection = null;
    let stashedContextSelection = null;
    let editingParameterName = null;

    const editor = new EditorView({
        state: EditorState.create({
            doc: initial.template,
            extensions: [
                basicSetup,
                getCodeLanguage(language),
                placeholderField,
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
            }

            return;
        }

        contextMenu.classList.add('d-none');

        if (clearSelection) {
            pendingSelection = null;
            stashedContextSelection = null;
        }
    }

    function showContextMenu(x, y, selection) {
        if (! contextMenu) {
            return;
        }

        pendingSelection = {
            from: selection.from,
            to: selection.to,
            text: editor.state.doc.sliceString(selection.from, selection.to),
        };

        contextMenu.style.left = `${x}px`;
        contextMenu.style.top = `${y}px`;
        contextMenu.classList.remove('d-none');
    }

    function clearModalError() {
        if (! modalError) {
            return;
        }

        modalError.textContent = '';
        modalError.classList.add('d-none');
    }

    function showModalError(message) {
        if (! modalError) {
            return;
        }

        modalError.textContent = message;
        modalError.classList.remove('d-none');
    }

    function openCreateModal() {
        editingParameterName = null;
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

        if (modalTitle) {
            modalTitle.textContent = 'Make dynamic';
        }

        if (modalSubmit) {
            modalSubmit.textContent = 'Add parameter';
        }

        if (paramTypeInput) {
            paramTypeInput.value = 'text';
            paramTypeInput.disabled = false;
        }

        const selectedText = pendingSelection?.text?.trim() || '';

        if (paramLabelInput) {
            paramLabelInput.value = selectedText;
        }

        if (paramNameInput) {
            paramNameInput.value = slugifyName(selectedText);
            paramNameInput.readOnly = false;
        }

        modal.show();
        paramLabelInput?.focus();
    }

    function openEditModal(parameter) {
        editingParameterName = parameter.name;
        clearModalError();
        hideContextMenu();

        if (modalTitle) {
            modalTitle.textContent = 'Edit parameter';
        }

        if (modalSubmit) {
            modalSubmit.textContent = 'Save changes';
        }

        if (paramTypeInput) {
            paramTypeInput.value = parameter.type || 'text';
        }

        if (paramLabelInput) {
            paramLabelInput.value = parameter.label || '';
        }

        if (paramNameInput) {
            paramNameInput.value = parameter.name;
            paramNameInput.readOnly = true;
        }

        modal.show();
        paramLabelInput?.focus();
    }

    function validateParameterInput(name, label, type) {
        if (! label.trim()) {
            return 'Label is required.';
        }

        if (! NAME_PATTERN.test(name)) {
            return 'Field name must start with a letter and contain only lowercase letters, numbers, and underscores.';
        }

        if (! PARAMETER_TYPE_LABELS[type]) {
            return 'Invalid field type.';
        }

        if (! editingParameterName && parameters.some((parameter) => parameter.name === name)) {
            return 'A parameter with this field name already exists.';
        }

        return null;
    }

    function renderParametersPanel() {
        parametersList.querySelectorAll('[data-dynamic-code-parameter-item]').forEach((item) => item.remove());

        if (parameters.length === 0) {
            parametersEmpty?.classList.remove('d-none');
            return;
        }

        parametersEmpty?.classList.add('d-none');

        parameters.forEach((parameter) => {
            const item = document.createElement('div');
            item.className = 'dynamic-code-parameters__item';
            item.dataset.dynamicCodeParameterItem = parameter.name;

            const typeLabel = PARAMETER_TYPE_LABELS[parameter.type] || parameter.type;

            item.innerHTML = `
                <div class="dynamic-code-parameters__item-main">
                    <div class="dynamic-code-parameters__item-label">${escapeHtml(parameter.label)}</div>
                    <div class="dynamic-code-parameters__item-meta">
                        <code class="dynamic-code-parameters__item-name">${escapeHtml(parameter.name)}</code>
                        <span class="badge text-bg-secondary">${escapeHtml(typeLabel)}</span>
                    </div>
                </div>
                <div class="dynamic-code-parameters__item-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dynamic-code-edit title="Edit parameter">Edit</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-dynamic-code-remove title="Remove parameter">Remove</button>
                </div>
            `;

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

    function addParameterFromModal() {
        const type = paramTypeInput?.value || 'text';
        const label = paramLabelInput?.value?.trim() || '';
        const name = paramNameInput?.value?.trim() || '';

        const error = validateParameterInput(name, label, type);

        if (error) {
            showModalError(error);
            return;
        }

        if (editingParameterName) {
            parameters = parameters.map((parameter) => {
                if (parameter.name !== editingParameterName) {
                    return parameter;
                }

                return {
                    ...parameter,
                    label,
                    type,
                };
            });
        } else {
            if (! pendingSelection) {
                showModalError('No text selected in the editor.');
                return;
            }

            const defaultValue = pendingSelection.text;
            parameters = [
                ...parameters,
                {
                    name,
                    label,
                    type,
                    default: defaultValue,
                },
            ];

            editor.dispatch({
                changes: {
                    from: pendingSelection.from,
                    to: pendingSelection.to,
                    insert: buildPlaceholderToken(name),
                },
            });
        }

        pendingSelection = null;
        stashedContextSelection = null;
        hideContextMenu(false);
        modal.hide();
        renderParametersPanel();
        syncHiddenInput();
    }

    async function removeParameter(name) {
        const parameter = parameters.find((item) => item.name === name);

        if (! parameter) {
            return;
        }

        const replacement = parameter.default ?? '';
        const ranges = findPlaceholderRanges(editor.state.doc, name);

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

    makeDynamicBtn?.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    makeDynamicBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        hideContextMenu(false);
        openCreateModal();
    });

    contextMenu?.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    modalSubmit?.addEventListener('click', addParameterFromModal);

    paramLabelInput?.addEventListener('input', () => {
        if (! paramNameInput || paramNameInput.readOnly || editingParameterName) {
            return;
        }

        paramNameInput.value = slugifyName(paramLabelInput.value);
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        clearModalError();
        editingParameterName = null;
        pendingSelection = null;
        stashedContextSelection = null;

        if (paramNameInput) {
            paramNameInput.readOnly = false;
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
}

export function initDynamicCodeEditors() {
    document.querySelectorAll('[data-dynamic-code-editor]').forEach(createDynamicCodeEditor);
}
