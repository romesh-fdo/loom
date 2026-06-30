function slugifyPageUrl(name) {
    return name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s/-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/\/+/g, '/')
        .replace(/-+/g, '-')
        .replace(/^[/\s-]+|[/\s-]+$/g, '');
}

export function initPageForm() {
    const form = document.querySelector('[data-loom-form="pages-basic"]');

    if (!form || form.dataset.pageFormInit === 'true') {
        return;
    }

    const nameInput = form.querySelector('#field-name');
    const urlInput = form.querySelector('#field-url');

    if (!nameInput || !urlInput) {
        return;
    }

    form.dataset.pageFormInit = 'true';

    const isEdit = form.querySelector('input[name="_method"][value="PUT"]') !== null;
    let urlDirty = isEdit || urlInput.value.trim() !== '';

    function syncUrlFromName() {
        if (urlDirty) {
            return;
        }

        urlInput.value = slugifyPageUrl(nameInput.value);
    }

    nameInput.addEventListener('input', syncUrlFromName);

    urlInput.addEventListener('input', () => {
        urlDirty = true;
    });

    if (!isEdit && nameInput.value.trim() !== '' && urlInput.value.trim() === '') {
        syncUrlFromName();
    }
}
