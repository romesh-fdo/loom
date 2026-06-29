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
    if (mount.dataset.initialized === 'true') {
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
        quill.root.innerHTML = textarea.value;
    }

    quill.on('text-change', () => {
        syncMount(mount);
    });

    mount.__quill = quill;
    mount.dataset.initialized = 'true';
    syncMount(mount);
}

export function initRichTextEditors(root = document) {
    root.querySelectorAll('[data-rich-text-editor]').forEach((mount) => {
        if (mount.dataset.initialized === 'true') {
            return;
        }

        createRichTextEditor(mount);
    });
}

export function syncRichTextEditors() {
    document.querySelectorAll('[data-rich-text-editor][data-initialized="true"]').forEach((mount) => {
        syncMount(mount);
    });
}
