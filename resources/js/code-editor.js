import { EditorView, keymap } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';
import { indentMore, indentLess } from '@codemirror/commands';
import { indentUnit } from '@codemirror/language';

const TAB_SPACES = '    ';

const tabIndentKeymap = {
    key: 'Tab',
    run: ({ state, dispatch }) => {
        if (state.selection.ranges.some((range) => !range.empty)) {
            return indentMore({ state, dispatch });
        }

        dispatch(state.update(state.replaceSelection(TAB_SPACES), {
            scrollIntoView: true,
            userEvent: 'input',
        }));

        return true;
    },
    shift: indentLess,
};

export { EditorView, Decoration, keymap } from '@codemirror/view';
export { EditorState, StateField, Prec } from '@codemirror/state';

export function getCodeLanguage(language) {
    if (language === 'javascript' || language === 'js') {
        return javascript();
    }

    return html();
}

export function buildCodeEditorTheme(options = {}) {
    const { minHeight = '280px', fontSize = '0.875rem' } = options;

    return EditorView.theme({
        '&': {
            minHeight,
            fontSize,
        },
        '.cm-scroller': {
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
        },
        '.cm-editor.cm-focused': {
            outline: '2px solid var(--loom-form-field-focus, #FF2E69)',
            outlineOffset: '-1px',
        },
    });
}

export function buildCodeEditorExtensions(options = {}) {
    const {
        language = 'html',
        lineWrap = false,
        minHeight = '280px',
        onDocChange = null,
        editable = true,
        extraExtensions = [],
    } = options;

    const extensions = [
        ...basicSetup,
        indentUnit.of(TAB_SPACES),
        keymap.of([tabIndentKeymap]),
        getCodeLanguage(language),
        buildCodeEditorTheme({ minHeight }),
        oneDark,
        EditorView.editable.of(editable),
    ];

    if (lineWrap) {
        extensions.push(EditorView.lineWrapping);
    }

    if (typeof onDocChange === 'function') {
        extensions.push(EditorView.updateListener.of((update) => {
            if (update.docChanged) {
                onDocChange(update.state.doc.toString(), update);
            }
        }));
    }

    if (extraExtensions.length > 0) {
        extensions.push(...extraExtensions);
    }

    return extensions;
}

export function createCodeEditor(mount, options = {}) {
    const targetId = mount.dataset.target;
    const textarea = targetId ? document.getElementById(targetId) : null;

    if (!textarea) {
        return null;
    }

    const language = mount.dataset.language || options.language || 'html';
    const isDisabled = mount.dataset.disabled === 'true';
    const isReadonly = mount.dataset.readonly === 'true';
    const lineWrap = mount.dataset.lineWrap === 'true' || options.lineWrap === true;
    const minHeight = mount.dataset.minHeight || options.minHeight || '280px';

    const extensionList = buildCodeEditorExtensions({
        language,
        lineWrap,
        minHeight,
        editable: !isDisabled && !isReadonly,
        onDocChange: (value) => {
            textarea.value = value;
        },
        extraExtensions: options.extraExtensions ?? [],
    });

    const editor = new EditorView({
        state: EditorState.create({ doc: textarea.value, extensions: extensionList }),
        parent: mount,
    });

    mount.dataset.initialized = 'true';
    mount.editorView = editor;

    if (document.activeElement === editor.contentDOM) {
        editor.contentDOM.blur();
    }

    return editor;
}

function queryCodeEditorMounts(root) {
    return (root instanceof Element ? root : document).querySelectorAll('[data-code-editor]');
}

export function syncCodeEditorMount(mount) {
    const targetId = mount.dataset.target;
    const textarea = targetId ? document.getElementById(targetId) : null;
    const editor = mount.editorView;

    if (!textarea || !editor) {
        return;
    }

    textarea.value = editor.state.doc.toString();
}

export function initCodeEditors(root = document) {
    const mounts = queryCodeEditorMounts(root);

    mounts.forEach((mount) => {
        if (mount.dataset.initialized === 'true') {
            return;
        }

        createCodeEditor(mount);
    });
}

export function syncCodeEditors(root = document) {
    queryCodeEditorMounts(root).forEach((mount) => {
        if (mount.dataset.initialized !== 'true') {
            return;
        }

        syncCodeEditorMount(mount);
    });
}

export function destroyCodeEditors(root = document) {
    queryCodeEditorMounts(root).forEach((mount) => {
        if (mount.editorView) {
            mount.editorView.destroy();
            mount.editorView = null;
        }

        delete mount.dataset.initialized;
    });
}
