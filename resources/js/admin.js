import * as bootstrap from 'bootstrap';
import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';

const THEME_KEY = 'admin-theme';

function getPreferredTheme() {
    const stored = localStorage.getItem(THEME_KEY);
    if (stored) return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem(THEME_KEY, theme);
    document.dispatchEvent(new CustomEvent('admin-theme-changed', { detail: { theme } }));
}

function getCodeLanguage(language) {
    if (language === 'javascript' || language === 'js') {
        return javascript();
    }

    return html();
}

function initCodeEditors() {
    document.querySelectorAll('[data-code-editor]').forEach((mount) => {
        const targetId = mount.dataset.target;
        const textarea = targetId ? document.getElementById(targetId) : null;

        if (!textarea || mount.dataset.initialized === 'true') {
            return;
        }

        const language = mount.dataset.language || 'html';
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const isDisabled = mount.dataset.disabled === 'true';
        const isReadonly = mount.dataset.readonly === 'true';

        const editor = new EditorView({
            doc: textarea.value,
            extensions: [
                basicSetup,
                getCodeLanguage(language),
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        textarea.value = update.state.doc.toString();
                    }
                }),
                EditorView.editable.of(!isDisabled && !isReadonly),
                EditorView.theme({
                    '&': {
                        minHeight: '280px',
                        fontSize: '0.875rem',
                    },
                    '.cm-scroller': {
                        fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
                    },
                }),
                ...(isDark ? [oneDark] : []),
            ],
            parent: mount,
        });

        mount.dataset.initialized = 'true';
        mount.editorView = editor;
    });

    document.addEventListener('admin-theme-changed', () => {
        window.location.reload();
    });
}

function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    toggle.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-bs-theme');
        setTheme(current === 'dark' ? 'light' : 'dark');
    });
}

function initSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const openBtn = document.getElementById('sidebar-toggle');
    const closeBtn = document.getElementById('sidebar-close');

    function openSidebar() {
        sidebar?.classList.add('show');
        overlay?.classList.add('show');
    }

    function closeSidebar() {
        sidebar?.classList.remove('show');
        overlay?.classList.remove('show');
    }

    openBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);
}

function initNavGroups() {
    document.querySelectorAll('[data-nav-group]').forEach((group) => {
        const toggle = group.querySelector('.admin-nav-parent');
        if (!toggle) return;

        toggle.addEventListener('click', () => {
            const isOpen = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });
}

const imageFilePattern = /\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i;

function isImageFile(file) {
    if (file.type?.startsWith('image/')) {
        return true;
    }

    return imageFilePattern.test(file.name);
}

function initFilePreviews() {
    document.querySelectorAll('[data-file-field]').forEach((field) => {
        if (field.dataset.filePreviewInit === 'true') {
            return;
        }

        const input = field.querySelector('[data-file-input]');
        const preview = field.querySelector('[data-file-preview]');
        const imageWrap = field.querySelector('[data-file-preview-image]');
        const fileWrap = field.querySelector('[data-file-preview-file]');
        const image = field.querySelector('[data-file-preview-img]');
        const fileName = field.querySelector('[data-file-preview-name]');

        if (!input || !preview || !imageWrap || !fileWrap || !image || !fileName) {
            return;
        }

        field.dataset.filePreviewInit = 'true';

        let objectUrl = null;

        const existingUrl = field.dataset.existingUrl || '';
        const existingName = field.dataset.existingName || '';
        const existingIsImage = field.dataset.existingIsImage === 'true';

        const show = (el) => el.classList.remove('d-none');
        const hide = (el) => el.classList.add('d-none');

        function revokeObjectUrl() {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
        }

        function showImage(src, alt) {
            hide(fileWrap);
            show(imageWrap);
            image.src = src;
            image.alt = alt;
            show(preview);
        }

        function showFileLabel(name) {
            hide(imageWrap);
            image.removeAttribute('src');
            show(fileWrap);
            fileName.textContent = name;
            show(preview);
        }

        function showExistingPreview() {
            if (!existingName) {
                return false;
            }

            if (existingIsImage && existingUrl) {
                showImage(existingUrl, existingName);
            } else {
                showFileLabel(existingName);
            }

            return true;
        }

        function clearPreview() {
            revokeObjectUrl();
            image.removeAttribute('src');
            image.alt = 'File preview';
            fileName.textContent = '';
            hide(imageWrap);
            hide(fileWrap);
            hide(preview);
        }

        input.addEventListener('change', () => {
            revokeObjectUrl();

            const file = input.files?.[0];
            if (!file) {
                if (!showExistingPreview()) {
                    clearPreview();
                }
                return;
            }

            if (isImageFile(file)) {
                objectUrl = URL.createObjectURL(file);
                showImage(objectUrl, file.name);
                return;
            }

            showFileLabel(file.name);
        });
    });
}

function initRepeaters() {
    document.querySelectorAll('[data-repeater]').forEach((repeater) => {
        if (repeater.dataset.initialized === 'true') {
            return;
        }

        const itemsEl = repeater.querySelector('[data-repeater-items]');
        const templateEl = repeater.querySelector('[data-repeater-prototype]');
        const addBtn = repeater.querySelector('[data-repeater-add]');
        const baseName = repeater.dataset.name;
        const itemLabel = repeater.dataset.itemLabel || 'Item';
        const min = parseInt(repeater.dataset.min || '0', 10);
        const max = repeater.dataset.max ? parseInt(repeater.dataset.max, 10) : null;
        const isDisabled = repeater.dataset.disabled === 'true';

        if (!itemsEl || isDisabled) {
            return;
        }

        function reindex() {
            const items = itemsEl.querySelectorAll(':scope > [data-repeater-item]');

            items.forEach((item, index) => {
                item.dataset.index = String(index);

                item.querySelectorAll('[name]').forEach((input) => {
                    const currentName = input.getAttribute('name');
                    if (!currentName || !currentName.startsWith(`${baseName}[`)) {
                        return;
                    }

                    input.name = currentName.replace(
                        new RegExp(`^${baseName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\[\\d+\\]`),
                        `${baseName}[${index}]`
                    );

                    if (input.id) {
                        input.id = input.id.replace(/-\d+-/, `-${index}-`);
                    }
                });

                item.querySelectorAll('label[for]').forEach((label) => {
                    const fieldId = label.getAttribute('for');
                    if (fieldId) {
                        label.setAttribute('for', fieldId.replace(/-\d+-/, `-${index}-`));
                    }
                });

                const title = item.querySelector('[data-repeater-item-label]');
                if (title) {
                    title.textContent = `${itemLabel} ${index + 1}`;
                }
            });

            if (addBtn) {
                addBtn.disabled = max !== null && items.length >= max;
            }

            items.forEach((item) => {
                const removeBtn = item.querySelector('[data-repeater-remove]');
                if (removeBtn) {
                    removeBtn.disabled = items.length <= min;
                }
            });

            if (itemsEl) {
                itemsEl.hidden = items.length === 0;
            }
        }

        addBtn?.addEventListener('click', () => {
            if (!templateEl?.content) {
                return;
            }

            const count = itemsEl.querySelectorAll(':scope > [data-repeater-item]').length;
            if (max !== null && count >= max) {
                return;
            }

            const fragment = templateEl.content.cloneNode(true);
            const item = fragment.querySelector('[data-repeater-item]');

            if (!item) {
                return;
            }

            item.classList.remove('d-none');
            itemsEl.appendChild(item);
            reindex();
        });

        repeater.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('[data-repeater-remove]');
            if (!removeBtn || !repeater.contains(removeBtn)) {
                return;
            }

            const item = removeBtn.closest('[data-repeater-item]');
            const count = itemsEl.querySelectorAll(':scope > [data-repeater-item]').length;

            if (!item || count <= min) {
                return;
            }

            item.remove();
            reindex();
        });

        repeater.dataset.initialized = 'true';
        reindex();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setTheme(getPreferredTheme());
    initThemeToggle();
    initSidebar();
    initNavGroups();
    initCodeEditors();
    initFilePreviews();
    initRepeaters();

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
    });
});
