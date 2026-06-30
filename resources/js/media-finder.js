import * as bootstrap from 'bootstrap';

const imageFilePattern = /\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i;

let activeFinderField = null;
let libraryPickerCallback = null;
let finderModalInstance = null;
let parameterModalInstance = null;
let activeParameterField = null;
let parameterModalState = { url: '', alt: '', class: '' };
let scriptLoadPromise = null;
let parameterModalBound = false;
let pickingParameterField = null;

function isImageUrl(url) {
    if (!url) {
        return false;
    }

    try {
        return imageFilePattern.test(new URL(url, window.location.origin).pathname);
    } catch {
        return imageFilePattern.test(url);
    }
}

function fileNameFromUrl(url) {
    if (!url) {
        return '';
    }

    try {
        return decodeURIComponent(new URL(url, window.location.origin).pathname.split('/').pop() || '');
    } catch {
        return url.split('/').pop() || '';
    }
}

function show(el) {
    el?.classList.remove('d-none');
}

function hide(el) {
    el?.classList.add('d-none');
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function getFinderModal() {
    return document.querySelector('[data-media-finder-modal]');
}

function getParameterModal() {
    return document.querySelector('[data-media-parameter-modal]');
}

function blurFocusedElement() {
    const active = document.activeElement;

    if (active && active !== document.body && typeof active.blur === 'function') {
        active.blur();
    }
}

function waitForModalHidden(modalEl) {
    if (!modalEl || !modalEl.classList.contains('show')) {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        modalEl.addEventListener('hidden.bs.modal', () => resolve(), { once: true });
    });
}

function resolvePickingParameterField() {
    return pickingParameterField ?? activeParameterField;
}

function normalizeFileUrl(fileUrl) {
    if (typeof fileUrl === 'string') {
        return fileUrl;
    }

    if (fileUrl && typeof fileUrl === 'object') {
        return fileUrl.url ?? fileUrl.path ?? '';
    }

    return '';
}

function syncParameterFieldUrl(field, url) {
    const resolvedUrl = normalizeFileUrl(url);
    const urlInput = field.querySelector('[data-media-param-url]');

    if (urlInput) {
        urlInput.value = resolvedUrl;
        urlInput.dispatchEvent(new Event('input', { bubbles: true }));
        urlInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    updateParameterTriggerPreview(field, resolvedUrl);
}

function readFieldState(field) {
    return {
        url: field.querySelector('[data-media-param-url]')?.value ?? '',
        alt: field.querySelector('[data-media-param-alt]')?.value ?? '',
        class: field.querySelector('[data-media-param-class]')?.value ?? '',
    };
}

function writeFieldState(field, state) {
    const urlInput = field.querySelector('[data-media-param-url]');
    const altInput = field.querySelector('[data-media-param-alt]');
    const classInput = field.querySelector('[data-media-param-class]');

    if (urlInput) {
        urlInput.value = state.url ?? '';
        urlInput.dispatchEvent(new Event('input', { bubbles: true }));
        urlInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (altInput) {
        altInput.value = state.alt ?? '';
    }

    if (classInput) {
        classInput.value = state.class ?? '';
    }

    updateParameterTriggerPreview(field, state.url);
}

function updatePreviewElements(scope, url) {
    const empty = scope.querySelector('[data-media-parameter-modal-empty]');
    const emptyTrigger = scope.querySelector('[data-media-parameter-empty]');
    const display = scope.querySelector('[data-media-parameter-display]');
    const imageWrap = scope.querySelector('[data-media-parameter-modal-preview-image], [data-media-parameter-preview-image]');
    const fileWrap = scope.querySelector('[data-media-parameter-modal-preview-file], [data-media-parameter-preview-file]');
    const image = scope.querySelector('[data-media-parameter-modal-preview-img], [data-media-parameter-preview-img]');
    const fileName = scope.querySelector('[data-media-parameter-modal-preview-name], [data-media-parameter-preview-name]');
    const previewLink = scope.querySelector('[data-media-parameter-preview-link]');
    const clearButton = scope.querySelector('[data-media-parameter-clear]');

    const hasUrl = String(url).trim() !== '';
    const name = fileNameFromUrl(url);

    if (empty) {
        empty.hidden = hasUrl;
    }

    if (!hasUrl) {
        show(emptyTrigger);
        hide(display);
        image?.removeAttribute('src');
        if (fileName) {
            fileName.textContent = '';
        }
        hide(imageWrap);
        hide(fileWrap);
        if (previewLink) {
            previewLink.href = '#';
            hide(previewLink);
        }

        return;
    }

    hide(emptyTrigger);
    show(display);

    if (previewLink) {
        previewLink.href = url;
        show(previewLink);
    }

    if (isImageUrl(url)) {
        hide(fileWrap);
        show(imageWrap);
        if (image) {
            image.src = url;
            image.alt = name;
        }
    } else {
        hide(imageWrap);
        image?.removeAttribute('src');
        show(fileWrap);
        if (fileName) {
            fileName.textContent = name;
        }
    }

    show(clearButton);
}

function updateParameterTriggerPreview(field, url) {
    updatePreviewElements(field, url);
}

function updateParameterModalPreview(url) {
    updatePreviewElements(getParameterModal(), url);
}

function updateFieldPreview(field, url) {
    const preview = field.querySelector('[data-media-finder-preview]');
    const imageWrap = field.querySelector('[data-media-finder-preview-image]');
    const fileWrap = field.querySelector('[data-media-finder-preview-file]');
    const image = field.querySelector('[data-media-finder-preview-img]');
    const fileName = field.querySelector('[data-media-finder-preview-name]');
    const clearButton = field.querySelector('[data-media-finder-clear]');

    if (!preview || !imageWrap || !fileWrap || !image || !fileName) {
        return;
    }

    if (!url) {
        image.removeAttribute('src');
        fileName.textContent = '';
        hide(imageWrap);
        hide(fileWrap);
        hide(preview);
        hide(clearButton);
        return;
    }

    const name = fileNameFromUrl(url);

    if (isImageUrl(url)) {
        hide(fileWrap);
        show(imageWrap);
        image.src = url;
        image.alt = name;
    } else {
        hide(imageWrap);
        image.removeAttribute('src');
        show(fileWrap);
        fileName.textContent = name;
    }

    show(preview);
    show(clearButton);
}

function setFinderFieldValue(field, url) {
    const input = field.querySelector('[data-media-finder-input]');

    if (!input) {
        return;
    }

    input.value = url;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    updateFieldPreview(field, url);
}

function fitFileManager() {
    const block = document.getElementById('fm-main-block');
    const modalBody = document.querySelector('[data-media-finder-modal-body]');

    if (!block || !modalBody) {
        return;
    }

    block.style.height = `${Math.max(320, modalBody.clientHeight)}px`;
}

function ensureFileManagerStyles(modal) {
    const cssHref = modal?.dataset.fileManagerCss;

    if (!cssHref || document.getElementById('file-manager-css')) {
        return;
    }

    const link = document.createElement('link');
    link.id = 'file-manager-css';
    link.rel = 'stylesheet';
    link.href = cssHref;
    document.head.appendChild(link);
}

async function prepareMediaDisk(prepareUrl) {
    const response = await fetch(prepareUrl, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to prepare media file manager.');
    }
}

function getFileManagerStore() {
    const fm = window.fm;

    if (!fm) {
        return null;
    }

    if (window.__loomFmStore) {
        return window.__loomFmStore;
    }

    if (fm.$store) {
        window.__loomFmStore = fm.$store;

        return fm.$store;
    }

    const internals = fm.$ ?? fm._ ?? null;
    const store = internals?.appContext?.config?.globalProperties?.$store ?? null;

    if (store) {
        window.__loomFmStore = store;
    }

    return store;
}

function waitForFileManagerStore(timeoutMs = 10000) {
    return new Promise((resolve, reject) => {
        const deadline = Date.now() + timeoutMs;

        const check = () => {
            const store = getFileManagerStore();

            if (store) {
                resolve(store);

                return;
            }

            if (Date.now() > deadline) {
                reject(new Error('File manager store failed to initialize.'));

                return;
            }

            requestAnimationFrame(check);
        };

        check();
    });
}

function loadFileManagerScript(src) {
    if (getFileManagerStore()) {
        return Promise.resolve();
    }

    if (scriptLoadPromise) {
        return scriptLoadPromise;
    }

    scriptLoadPromise = new Promise((resolve, reject) => {
        const finish = () => {
            waitForFileManagerStore()
                .then(resolve)
                .catch((error) => {
                    scriptLoadPromise = null;
                    reject(error);
                });
        };

        if (document.getElementById('file-manager-script')) {
            finish();

            return;
        }

        const script = document.createElement('script');
        script.id = 'file-manager-script';
        script.src = src;
        script.addEventListener('load', finish);
        script.addEventListener('error', () => {
            scriptLoadPromise = null;
            reject(new Error('Failed to load file manager.'));
        });
        document.body.appendChild(script);
    });

    return scriptLoadPromise;
}

function registerFileCallback() {
    const store = getFileManagerStore();

    if (!store) {
        return false;
    }

    store.commit('fm/setFileCallBack', (fileUrl) => {
        const url = normalizeFileUrl(fileUrl);
        const field = resolvePickingParameterField();

        blurFocusedElement();

        if (libraryPickerCallback) {
            libraryPickerCallback(url);
        } else if (activeFinderField) {
            setFinderFieldValue(activeFinderField, url);
        }

        if (field) {
            syncParameterFieldUrl(field, url);
        }

        parameterModalState.url = url;

        pickingParameterField = null;
        activeParameterField = null;

        finderModalInstance?.hide();
    });

    return true;
}

function clearFileCallback() {
    const store = getFileManagerStore();

    if (store) {
        store.commit('fm/setFileCallBack', null);
    }
}

async function openLibraryModal(onSelect, options = {}) {
    const modal = getFinderModal();

    if (!modal) {
        return;
    }

    const block = modal.querySelector('#fm-main-block');
    const prepareUrl = modal.dataset.prepareUrl;
    const scriptSrc = block?.dataset.fileManagerSrc;

    if (!prepareUrl || !scriptSrc) {
        return;
    }

    const hideParameterModal = options.hideParameterModal === true
        && activeParameterField
        && parameterModalInstance;

    pickingParameterField = hideParameterModal ? activeParameterField : null;

    libraryPickerCallback = onSelect ?? null;
    activeFinderField = onSelect ? null : activeFinderField;

    if (!finderModalInstance) {
        finderModalInstance = bootstrap.Modal.getOrCreateInstance(modal);
        modal.addEventListener('shown.bs.modal', () => {
            fitFileManager();
            registerFileCallback();
        });
        modal.addEventListener('hidden.bs.modal', () => {
            clearFileCallback();
            libraryPickerCallback = null;

            if (!pickingParameterField) {
                activeFinderField = null;
            }

            pickingParameterField = null;
        });
        window.addEventListener('resize', fitFileManager);
    }

    try {
        if (hideParameterModal) {
            blurFocusedElement();
            parameterModalInstance.hide();
            await waitForModalHidden(getParameterModal());
        }

        ensureFileManagerStyles(modal);
        await prepareMediaDisk(prepareUrl);
        await loadFileManagerScript(scriptSrc);
        await waitForFileManagerStore();
        registerFileCallback();
        blurFocusedElement();
        finderModalInstance.show();
        requestAnimationFrame(fitFileManager);
    } catch (error) {
        console.error(error);
        libraryPickerCallback = null;
        activeFinderField = null;
        pickingParameterField = null;
        activeParameterField = null;
    }
}

async function uploadMediaFile(file) {
    const modal = getParameterModal();
    const uploadUrl = modal?.dataset.uploadUrl;

    if (!uploadUrl || !file) {
        return null;
    }

    const body = new FormData();
    body.append('file', file);

    const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
        body,
    });

    if (!response.ok) {
        throw new Error('Upload failed.');
    }

    return response.json();
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

    const altInput = modal.querySelector('[data-media-parameter-modal-alt]');
    const classInput = modal.querySelector('[data-media-parameter-modal-class]');
    const uploadInput = modal.querySelector('[data-media-parameter-upload-input]');
    const uploadTrigger = modal.querySelector('[data-media-parameter-upload-trigger]');
    const libraryTrigger = modal.querySelector('[data-media-parameter-library-trigger]');
    const applyButton = modal.querySelector('[data-media-parameter-modal-apply]');
    const clearButton = modal.querySelector('[data-media-parameter-modal-clear]');

    uploadTrigger?.addEventListener('click', () => {
        uploadInput?.click();
    });

    uploadInput?.addEventListener('change', async () => {
        const file = uploadInput.files?.[0];

        if (!file) {
            return;
        }

        try {
            uploadTrigger.disabled = true;
            const payload = await uploadMediaFile(file);
            parameterModalState.url = payload?.url ?? '';
            updateParameterModalPreview(parameterModalState.url);

            if (activeParameterField) {
                syncParameterFieldUrl(activeParameterField, parameterModalState.url);
            }
        } catch (error) {
            console.error(error);
        } finally {
            uploadTrigger.disabled = false;
            uploadInput.value = '';
        }
    });

    libraryTrigger?.addEventListener('click', () => {
        const field = activeParameterField;

        openLibraryModal((url) => {
            parameterModalState.url = url;

            if (field) {
                syncParameterFieldUrl(field, url);
            }
        }, { hideParameterModal: true });
    });

    clearButton?.addEventListener('click', () => {
        parameterModalState.url = '';
        updateParameterModalPreview('');
    });

    applyButton?.addEventListener('click', () => {
        const field = resolvePickingParameterField() ?? activeParameterField;

        if (!field) {
            return;
        }

        parameterModalState.alt = altInput?.value ?? '';
        parameterModalState.class = classInput?.value ?? '';
        writeFieldState(field, parameterModalState);
        pickingParameterField = null;
        parameterModalInstance.hide();
    });

    modal.addEventListener('hidden.bs.modal', () => {
        if (pickingParameterField) {
            return;
        }

        activeParameterField = null;
    });
}

function configureParameterModalActions(mode) {
    const modal = getParameterModal();

    if (!modal) {
        return;
    }

    const isAttach = mode === 'attach';
    const uploadTrigger = modal.querySelector('[data-media-parameter-upload-trigger]');
    const libraryTrigger = modal.querySelector('[data-media-parameter-library-trigger]');

    uploadTrigger?.classList.toggle('d-none', !isAttach);
    libraryTrigger?.classList.toggle('d-none', isAttach);
}

function openMediaParameterModal(field) {
    const modal = getParameterModal();

    if (!modal || field.dataset.disabled === 'true') {
        return;
    }

    bindParameterModal();

    activeParameterField = field;
    parameterModalState = readFieldState(field);
    const mode = field.dataset.mediaMode === 'attach' ? 'attach' : 'selector';

    configureParameterModalActions(mode);

    modal.querySelector('[data-media-parameter-modal-alt]').value = parameterModalState.alt;
    modal.querySelector('[data-media-parameter-modal-class]').value = parameterModalState.class;
    updateParameterModalPreview(parameterModalState.url);

    const label = field.querySelector('.form-label')?.textContent?.trim() || 'Media';

    const title = modal.querySelector('#loom-media-parameter-modal-label');

    if (title) {
        title.textContent = label;
    }

    parameterModalInstance.show();
}

function initMediaParameterField(field) {
    if (field.dataset.mediaParameterInit === 'true') {
        return;
    }

    field.dataset.mediaParameterInit = 'true';

    const openButtons = field.querySelectorAll('[data-media-parameter-open]');
    const clearButton = field.querySelector('[data-media-parameter-clear]');

    updateParameterTriggerPreview(field, readFieldState(field).url);

    openButtons.forEach((openButton) => {
        openButton.addEventListener('click', (event) => {
            event.preventDefault();
            openMediaParameterModal(field);
        });
    });

    clearButton?.addEventListener('click', (event) => {
        event.preventDefault();
        writeFieldState(field, { url: '', alt: '', class: '' });
    });
}

function initMediaFinderField(field) {
    if (field.dataset.mediaFinderInit === 'true') {
        return;
    }

    const openButton = field.querySelector('[data-media-finder-open]');
    const clearButton = field.querySelector('[data-media-finder-clear]');
    const input = field.querySelector('[data-media-finder-input]');

    if (!openButton || !input) {
        return;
    }

    field.dataset.mediaFinderInit = 'true';

    const existingUrl = field.dataset.existingUrl || input.value || '';

    if (existingUrl) {
        updateFieldPreview(field, existingUrl);
    }

    openButton.addEventListener('click', (event) => {
        event.preventDefault();
        activeFinderField = field;
        openLibraryModal();
    });

    clearButton?.addEventListener('click', (event) => {
        event.preventDefault();
        setFinderFieldValue(field, '');
    });
}

export function initMediaFinders(root = document) {
    const finderModal = getFinderModal();

    if (finderModal && !finderModal.dataset.prepareUrl) {
        finderModal.dataset.prepareUrl = finderModal.getAttribute('data-prepare-url') || '';
    }

    bindParameterModal();

    root.querySelectorAll('[data-media-parameter-field]').forEach(initMediaParameterField);
    root.querySelectorAll('[data-media-finder-field]').forEach(initMediaFinderField);
}
