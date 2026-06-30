import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const TOOLBAR_OPTIONS = [
    ['bold', 'italic', 'underline', 'strike'],
    [{ header: [1, 2, 3, false] }],
    ['blockquote'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ indent: '-1' }, { indent: '+1' }],
    ['link'],
    ['clean'],
];

function queryRichTextMounts(root) {
    return (root instanceof Element ? root : document).querySelectorAll('[data-rich-text-editor]');
}

function syncMount(mount) {
    const targetId = mount.dataset.target;
    const textarea = targetId ? document.getElementById(targetId) : null;
    const quill = mount.__quill;

    if (! textarea || ! quill) {
        return;
    }

    textarea.value = quill.root.innerHTML;
}

function createRichTextEditor(mount) {
    if (mount.dataset.initialized === 'true' || mount.__quill) {
        return;
    }

    const targetId = mount.dataset.target;
    const textarea = targetId ? document.getElementById(targetId) : null;

    if (! textarea) {
        return;
    }

    const isDisabled = mount.dataset.disabled === 'true';
    const isReadonly = mount.dataset.readonly === 'true';

    const quill = new Quill(mount, {
        theme: 'snow',
        modules: {
            toolbar: isDisabled || isReadonly ? false : TOOLBAR_OPTIONS,
        },
        readOnly: isDisabled || isReadonly,
    });

    if (textarea.value) {
        quill.clipboard.dangerouslyPasteHTML(textarea.value);
    }

    quill.on('text-change', () => {
        syncMount(mount);
    });

    mount.__quill = quill;
    mount.dataset.initialized = 'true';
    syncMount(mount);
    quill.setSelection(null);
}

export function initRichTextEditors(root = document) {
    queryRichTextMounts(root).forEach((mount) => {
        if (mount.dataset.initialized === 'true') {
            return;
        }

        createRichTextEditor(mount);
    });
}

export function destroyRichTextEditors(root = document) {
    queryRichTextMounts(root).forEach((mount) => {
        if (mount.__quill) {
            mount.__quill.off('text-change');
            mount.__quill = null;
        }

        const field = mount.closest('.loom-rich-text-field');
        field?.querySelector(':scope > .ql-toolbar')?.remove();

        mount.innerHTML = '';
        mount.classList.remove('ql-snow', 'ql-container');
        delete mount.dataset.initialized;
    });
}

export function syncRichTextEditors(root = document) {
    queryRichTextMounts(root).forEach((mount) => {
        if (mount.dataset.initialized !== 'true') {
            return;
        }

        syncMount(mount);
    });
}
