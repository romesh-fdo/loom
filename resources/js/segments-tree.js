import { confirmAction, promptInput, setActionSubmitLabel, showAdminToast } from './admin-notifications';
import * as bootstrap from 'bootstrap';
import { syncDynamicCodeEditors, initDynamicCodeEditors } from './dynamic-code-editor';
import { initCodeEditors, syncCodeEditors } from './code-editor';
import { syncRichTextEditors, initRichTextEditors } from './rich-text-editor';
import { initMediaFinders } from './media-finder';
import { initUrlParameters } from './url-parameter';

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function jsonHeaders() {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Segments-Panel': '1',
    };
}

function panelHeaders() {
    return {
        Accept: 'text/html',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Segments-Panel': '1',
    };
}

function formatValidationMessage(payload, response) {
    if (response?.status === 419) {
        return 'Session expired. Refresh the page and try again.';
    }

    if (payload?.errors && typeof payload.errors === 'object') {
        const firstField = Object.keys(payload.errors)[0];
        const firstMessage = firstField ? payload.errors[firstField]?.[0] : null;

        if (firstMessage) {
            return firstMessage;
        }
    }

    return payload?.message || 'Please fix the validation errors.';
}

function encodeSegmentPath(slug) {
    return slug.split('/').map((part) => encodeURIComponent(part)).join('/');
}

function slugifyFolderName(name) {
    return name.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function validateFolderName(name) {
    const slug = slugifyFolderName(name);

    if (!slug) {
        return 'Use letters, numbers, or hyphens only.';
    }

    if (!/^[a-z0-9][a-z0-9-]*$/.test(slug)) {
        return 'Folder name must start with a letter or number.';
    }

    return null;
}

function segmentBasename(slug) {
    return slug.includes('/') ? slug.slice(slug.lastIndexOf('/') + 1) : slug;
}

function segmentDirname(slug) {
    return slug.includes('/') ? slug.slice(0, slug.lastIndexOf('/')) : '';
}

function joinPath(folder, name) {
    return folder ? `${folder}/${name}` : name;
}

function validateSegmentName(name) {
    return validateFolderName(name);
}

function validateSegmentRenameName(name) {
    const trimmed = name.trim();

    if (!trimmed) {
        return 'Name is required.';
    }

    if (!slugifyFolderName(trimmed)) {
        return 'Use letters or numbers in the name.';
    }

    return null;
}

function formatFolderPath(path) {
    if (! path) {
        return 'segments';
    }

    return `segments / ${path.split('/').join(' / ')}`;
}

class SegmentsExplorer {
    constructor(root) {
        this.root = root;
        this.treeEl = root.querySelector('#segments-tree');
        this.formEmpty = root.querySelector('#segments-form-empty');
        this.folderContext = root.querySelector('#segments-folder-context');
        this.formContent = root.querySelector('#segments-form-content');
        this.treeUrl = root.dataset.treeUrl;
        this.formCreateUrl = root.dataset.formCreateUrl;
        this.formEditBase = root.dataset.formEditBase?.replace(/\/$/, '') ?? '';
        this.panelDestroyBase = root.dataset.panelDestroyBase?.replace(/\/$/, '') ?? '';
        this.foldersBase = root.dataset.foldersBase?.replace(/\/$/, '') ?? '';
        this.selectedFolder = null;
        this.selectedSegment = '';
        this.treeData = [];
        this.expandedPaths = new Set(['']);
        this.contextMenuEl = null;
        this.contextTarget = null;
        this.folderModal = null;
        this.folderResolve = null;

        this.bindFormPanel();
        this.bindContextMenu();
        this.bindFolderModal();
        this.loadTree().then(() => this.handleInitialState());
    }

    bindFormPanel() {
        this.formContent.addEventListener('click', (event) => {
            const cancel = event.target.closest('[data-segments-cancel]');

            if (cancel) {
                event.preventDefault();
                this.clearFormPanel();
            }

            const deleteTrigger = event.target.closest('[data-segments-delete]');

            if (deleteTrigger) {
                event.preventDefault();
                this.deleteSegment(deleteTrigger);
            }
        });

        this.formContent.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-segments-panel-form')) {
                return;
            }

            event.preventDefault();
            this.saveForm(form);
        });
    }

    async handleInitialState() {
        const initialSegment = this.root.dataset.initialSegment || '';
        const initialCreate = this.root.dataset.initialCreate === '1';
        const initialFolder = this.root.dataset.initialFolder || '';

        if (initialSegment) {
            this.selectedSegment = initialSegment;
            this.selectedFolder = initialSegment.includes('/')
                ? initialSegment.slice(0, initialSegment.lastIndexOf('/'))
                : '';
            await this.openEditForm(initialSegment);
            this.renderTree();

            return;
        }

        if (initialCreate) {
            this.selectedFolder = initialFolder;
            await this.openCreateForm(initialFolder);
            this.renderTree();
        }
    }

    async loadTree() {
        const response = await fetch(this.treeUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            showAdminToast('Could not load segments tree.', 'error');

            return;
        }

        const payload = await response.json();
        this.treeData = payload.tree ?? [];
        this.renderTree();
    }

    renderTree() {
        if (!this.treeEl) {
            return;
        }

        this.treeEl.innerHTML = '';
        this.treeEl.appendChild(this.renderRootRow());
    }

    renderRootRow() {
        const li = document.createElement('li');
        li.setAttribute('role', 'treeitem');

        const row = document.createElement('div');
        row.className = 'segments-tree-node segments-tree-node--root';
        row.dataset.path = '';
        row.dataset.type = 'folder';

        if (this.selectedFolder === '' && ! this.selectedSegment) {
            row.classList.add('is-active');
        }

        const isOpen = this.expandedPaths.has('');
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'segments-tree-toggle';
        toggle.setAttribute('aria-label', 'Toggle segments root');
        toggle.innerHTML = `<i class="bi bi-chevron-${isOpen ? 'down' : 'right'}"></i>`;
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            this.toggleFolder('');
        });
        row.appendChild(toggle);

        const icon = document.createElement('i');
        icon.className = 'bi bi-folder2 segments-tree-icon';
        row.appendChild(icon);

        row.addEventListener('click', () => {
            this.selectFolder('');
        });

        const label = document.createElement('span');
        label.className = 'segments-tree-node-label';
        label.textContent = 'segments';
        row.appendChild(label);

        this.attachCreateActions(row, '');

        li.appendChild(row);

        const rootNode = this.treeData[0];
        const children = rootNode?.children ?? [];

        if (children.length > 0 && this.expandedPaths.has('')) {
            li.appendChild(this.renderBranch(children));
        }

        return li;
    }

    renderBranch(children) {
        const ul = document.createElement('ul');
        ul.className = 'segments-tree-branch';
        ul.setAttribute('role', 'group');

        children.forEach((node) => {
            ul.appendChild(this.renderNode(node));
        });

        return ul;
    }

    renderNode(node) {
        const li = document.createElement('li');
        li.setAttribute('role', 'treeitem');

        const row = document.createElement('div');
        row.className = 'segments-tree-node';
        row.dataset.path = node.path ?? '';
        row.dataset.type = node.type ?? '';

        if (node.type === 'segment' && node.slug === this.selectedSegment) {
            row.classList.add('selected');
        }

        if (node.type === 'folder' && this.selectedFolder === node.path && ! this.selectedSegment) {
            row.classList.add('is-active');
        }

        if (node.type === 'folder') {
            const isOpen = this.expandedPaths.has(node.path);
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'segments-tree-toggle';
            toggle.setAttribute('aria-label', 'Toggle folder');
            toggle.innerHTML = `<i class="bi bi-chevron-${isOpen ? 'down' : 'right'}"></i>`;
            toggle.addEventListener('click', (event) => {
                event.stopPropagation();
                this.toggleFolder(node.path);
            });
            row.appendChild(toggle);

            const icon = document.createElement('i');
            icon.className = 'bi bi-folder2 segments-tree-icon';
            row.appendChild(icon);

            row.addEventListener('click', () => {
                this.selectFolder(node.path);
            });
        } else if (node.type === 'segment') {
            const spacer = document.createElement('span');
            spacer.className = 'segments-tree-toggle';
            spacer.setAttribute('aria-hidden', 'true');
            row.appendChild(spacer);

            const icon = document.createElement('i');
            icon.className = 'bi bi-file-earmark-code segments-tree-icon';
            row.appendChild(icon);

            row.addEventListener('click', () => {
                this.openEditForm(node.slug);
            });
        }

        const label = document.createElement('span');
        label.className = 'segments-tree-node-label';
        label.textContent = node.name ?? node.path;
        row.appendChild(label);

        if (node.type === 'folder') {
            this.attachCreateActions(row, node.path);
            this.attachContextMenu(label, {
                type: 'folder',
                path: node.path,
                name: node.name ?? segmentBasename(node.path),
                hasContents: this.nodeHasContents(node),
            });
        } else if (node.type === 'segment') {
            this.attachContextMenu(label, { type: 'segment', path: node.slug, name: node.name ?? segmentBasename(node.slug) });
        }

        li.appendChild(row);

        if (node.type === 'folder' && node.children?.length && this.expandedPaths.has(node.path)) {
            li.appendChild(this.renderBranch(node.children));
        }

        return li;
    }

    attachCreateActions(row, folderPath) {
        const actions = document.createElement('span');
        actions.className = 'segments-tree-node-actions';

        const newFile = document.createElement('button');
        newFile.type = 'button';
        newFile.className = 'segments-tree-node-action';
        newFile.title = 'New file';
        newFile.setAttribute('aria-label', 'New file');
        newFile.innerHTML = '<i class="bi bi-file-earmark-plus"></i>';
        newFile.addEventListener('click', (event) => {
            event.stopPropagation();
            this.openCreateForm(folderPath);
        });

        const newFolder = document.createElement('button');
        newFolder.type = 'button';
        newFolder.className = 'segments-tree-node-action';
        newFolder.title = 'New folder';
        newFolder.setAttribute('aria-label', 'New folder');
        newFolder.innerHTML = '<i class="bi bi-folder-plus"></i>';
        newFolder.addEventListener('click', (event) => {
            event.stopPropagation();
            this.createFolderIn(folderPath);
        });

        actions.append(newFile, newFolder);
        row.appendChild(actions);
    }

    bindContextMenu() {
        document.addEventListener('click', () => this.hideContextMenu());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.hideContextMenu();
            }
        });
    }

    attachContextMenu(label, item) {
        label.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.showContextMenu(event, item);
        });
    }

    ensureContextMenu() {
        if (this.contextMenuEl) {
            return;
        }

        this.contextMenuEl = document.createElement('div');
        this.contextMenuEl.className = 'segments-tree-context-menu';
        this.contextMenuEl.setAttribute('role', 'menu');
        this.contextMenuEl.hidden = true;
        document.body.appendChild(this.contextMenuEl);

        this.contextMenuEl.addEventListener('click', (event) => {
            const button = event.target.closest('[data-context-action]');

            if (!button) {
                return;
            }

            event.preventDefault();
            const action = button.dataset.contextAction;
            const target = this.contextTarget;

            this.hideContextMenu();

            if (!target) {
                return;
            }

            if (action === 'rename') {
                this.handleRename(target);
            } else if (action === 'move') {
                this.handleMove(target);
            } else if (action === 'delete') {
                this.handleDelete(target);
            }
        });
    }

    showContextMenu(event, item) {
        this.ensureContextMenu();
        this.contextTarget = item;

        this.contextMenuEl.innerHTML = `
            <button type="button" class="segments-tree-context-menu__item" data-context-action="rename" role="menuitem">
                <i class="bi bi-pencil"></i> Rename
            </button>
            <button type="button" class="segments-tree-context-menu__item" data-context-action="move" role="menuitem">
                <i class="bi bi-folder-symlink"></i> Move to…
            </button>
            <button type="button" class="segments-tree-context-menu__item segments-tree-context-menu__item--danger" data-context-action="delete" role="menuitem">
                <i class="bi bi-trash"></i> Delete
            </button>
        `;

        this.contextMenuEl.hidden = false;

        const menuRect = { width: 180, height: 120 };
        let left = event.clientX;
        let top = event.clientY;

        if (left + menuRect.width > window.innerWidth) {
            left = window.innerWidth - menuRect.width - 8;
        }

        if (top + menuRect.height > window.innerHeight) {
            top = window.innerHeight - menuRect.height - 8;
        }

        this.contextMenuEl.style.left = `${left}px`;
        this.contextMenuEl.style.top = `${top}px`;
    }

    hideContextMenu() {
        if (!this.contextMenuEl) {
            return;
        }

        this.contextMenuEl.hidden = true;
        this.contextTarget = null;
    }

    bindFolderModal() {
        const element = document.getElementById('segments-folder-modal');

        if (!element) {
            return;
        }

        this.folderModal = new bootstrap.Modal(element);
        const form = element.querySelector('[data-segments-folder-form]');
        const select = element.querySelector('[data-segments-folder-select]');

        const cancel = () => {
            this.folderResolve?.(null);
            this.folderResolve = null;
        };

        element.querySelectorAll('[data-segments-folder-cancel]').forEach((btn) => {
            btn.addEventListener('click', cancel);
        });

        element.addEventListener('hidden.bs.modal', () => {
            if (this.folderResolve) {
                this.folderResolve(null);
                this.folderResolve = null;
            }
        });

        form?.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!select) {
                return;
            }

            this.folderModal.hide();
            this.folderResolve?.(select.value);
            this.folderResolve = null;
        });
    }

    collectFolderOptions(excludePrefix = null) {
        const options = [{ path: '', label: 'segments' }];
        const root = this.treeData[0];
        const children = root?.children ?? [];

        const walk = (nodes) => {
            nodes.forEach((node) => {
                if (node.type !== 'folder') {
                    return;
                }

                if (excludePrefix !== null && (node.path === excludePrefix || node.path.startsWith(`${excludePrefix}/`))) {
                    return;
                }

                options.push({ path: node.path, label: formatFolderPath(node.path) });

                if (node.children?.length) {
                    walk(node.children);
                }
            });
        };

        walk(children);

        return options;
    }

    promptFolderSelect({ title = 'Move to folder', excludePrefix = null } = {}) {
        const element = document.getElementById('segments-folder-modal');

        if (!element || !this.folderModal) {
            return Promise.resolve(null);
        }

        const titleEl = element.querySelector('[data-segments-folder-title]');
        const select = element.querySelector('[data-segments-folder-select]');

        if (titleEl) {
            titleEl.textContent = title;
        }

        if (select) {
            select.innerHTML = '';
            this.collectFolderOptions(excludePrefix).forEach((option) => {
                const item = document.createElement('option');
                item.value = option.path;
                item.textContent = option.label;
                select.appendChild(item);
            });
        }

        return new Promise((resolve) => {
            this.folderResolve = resolve;
            this.folderModal.show();
        });
    }

    async handleRename(item) {
        if (item.type === 'folder') {
            await this.renameFolder(item.path, item.name);
        } else {
            await this.renameSegment(item.path, item.name);
        }
    }

    async handleMove(item) {
        if (item.type === 'folder') {
            await this.moveFolder(item.path);
        } else {
            await this.moveSegment(item.path);
        }
    }

    async handleDelete(item) {
        if (item.type === 'folder') {
            await this.deleteFolder(item.path, item.hasContents);
        } else {
            await this.deleteSegmentFromTree(item.path, item.name);
        }
    }

    async renameSegment(slug, currentName) {
        const name = await promptInput({
            title: 'Rename segment',
            label: 'Name',
            defaultValue: currentName,
            confirmLabel: 'Rename',
            hint: 'Letters, numbers, spaces, and hyphens. The file name is generated from this.',
            validate: validateSegmentRenameName,
        });

        if (!name) {
            return;
        }

        const displayName = name.trim();
        const filename = slugifyFolderName(name);
        const parent = segmentDirname(slug);
        const newSlug = joinPath(parent, filename);

        if (newSlug === slug && displayName === currentName) {
            return;
        }

        await this.performSegmentMove(slug, newSlug, 'Segment renamed.', displayName);
    }

    async moveSegment(slug) {
        const dest = await this.promptFolderSelect({
            title: 'Move segment',
            excludePrefix: null,
        });

        if (dest === null) {
            return;
        }

        const newSlug = joinPath(dest, segmentBasename(slug));

        if (newSlug === slug) {
            return;
        }

        await this.performSegmentMove(slug, newSlug);
    }

    async performSegmentMove(fromSlug, toSlug, successMessage = 'Segment moved.', displayName = null) {
        const body = { path: toSlug };

        if (displayName !== null) {
            body.name = displayName;
        }

        const response = await fetch(`${this.panelDestroyBase}/${encodeSegmentPath(fromSlug)}/move`, {
            method: 'PUT',
            headers: jsonHeaders(),
            body: JSON.stringify(body),
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return;
        }

        showAdminToast(payload.message || successMessage, 'success');

        if (this.selectedSegment === fromSlug) {
            this.selectedSegment = toSlug;
        }

        this.selectedFolder = segmentDirname(toSlug);
        this.expandedPaths.add(this.selectedFolder);

        let ancestor = this.selectedFolder;

        while (ancestor.includes('/')) {
            ancestor = ancestor.slice(0, ancestor.lastIndexOf('/'));
            this.expandedPaths.add(ancestor);
        }

        this.expandedPaths.add('');

        await this.loadTree();

        if (this.selectedSegment) {
            await this.openEditForm(this.selectedSegment);
        }
    }

    async moveFolder(path) {
        const dest = await this.promptFolderSelect({
            title: 'Move folder',
            excludePrefix: path,
        });

        if (dest === null) {
            return;
        }

        const baseName = segmentBasename(path);
        const newPath = joinPath(dest, baseName);

        if (newPath === path) {
            return;
        }

        const response = await fetch(`${this.foldersBase}/${encodeSegmentPath(path)}`, {
            method: 'PUT',
            headers: jsonHeaders(),
            body: JSON.stringify({ path: newPath }),
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return;
        }

        showAdminToast(payload.message || 'Folder moved.', 'success');

        if (this.selectedFolder === path || this.selectedFolder?.startsWith(`${path}/`)) {
            const suffix = this.selectedFolder === path ? '' : this.selectedFolder.slice(path.length);
            this.selectedFolder = `${newPath}${suffix}`;
        }

        if (this.selectedSegment.startsWith(`${path}/`)) {
            this.selectedSegment = `${newPath}${this.selectedSegment.slice(path.length)}`;
        }

        let ancestor = newPath;

        while (ancestor !== '') {
            this.expandedPaths.add(ancestor);

            if (!ancestor.includes('/')) {
                break;
            }

            ancestor = ancestor.slice(0, ancestor.lastIndexOf('/'));
        }

        this.expandedPaths.add('');

        await this.loadTree();

        if (this.selectedSegment) {
            await this.openEditForm(this.selectedSegment);
        } else if (this.selectedFolder !== null) {
            this.showFolderContext(this.selectedFolder);
        }
    }

    async deleteSegmentFromTree(slug, name) {
        const confirmed = await confirmAction({
            title: 'Delete segment',
            message: `Delete "${name}"?`,
            confirmLabel: 'Delete',
            danger: true,
        });

        if (!confirmed) {
            return;
        }

        const response = await fetch(`${this.panelDestroyBase}/${encodeSegmentPath(slug)}`, {
            method: 'DELETE',
            headers: jsonHeaders(),
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return;
        }

        showAdminToast(payload.message || 'Segment deleted.', 'success');

        if (this.selectedSegment === slug) {
            this.clearFormPanel();
        }

        await this.loadTree();
    }

    toggleFolder(path) {
        if (this.expandedPaths.has(path)) {
            this.expandedPaths.delete(path);
        } else {
            this.expandedPaths.add(path);
        }

        this.renderTree();
    }

    selectFolder(path) {
        this.selectedFolder = path;
        this.selectedSegment = '';
        this.expandedPaths.add(path);
        this.renderTree();
        this.showFolderContext(path);
    }

    showFolderContext(path) {
        if (! this.folderContext) {
            return;
        }

        this.folderContext.innerHTML = `
            <div class="segments-parent-folder">
                <i class="bi bi-folder2 segments-parent-folder-icon" aria-hidden="true"></i>
                <div class="segments-parent-folder-text">
                    <span class="segments-parent-folder-label">Location</span>
                    <span class="segments-parent-folder-path">${formatFolderPath(path)}</span>
                </div>
            </div>
        `;

        this.formEmpty.classList.add('d-none');
        this.formContent.classList.add('d-none');
        this.formContent.innerHTML = '';
        this.folderContext.classList.remove('d-none');
    }

    showEmptyState() {
        this.formEmpty.classList.remove('d-none');
        this.folderContext?.classList.add('d-none');
        this.formContent.classList.add('d-none');
        this.formContent.innerHTML = '';
    }

    clearFormPanel() {
        this.selectedSegment = '';

        if (this.selectedFolder !== null) {
            this.showFolderContext(this.selectedFolder);
        } else {
            this.showEmptyState();
        }

        this.renderTree();
    }

    async openCreateForm(folder = '') {
        this.selectedFolder = folder;
        this.selectedSegment = '';
        this.renderTree();

        const url = new URL(this.formCreateUrl, window.location.origin);

        if (folder) {
            url.searchParams.set('folder', folder);
        }

        await this.loadFormPanel(url.toString());
    }

    async openEditForm(slug) {
        this.selectedSegment = slug;
        this.selectedFolder = slug.includes('/') ? slug.slice(0, slug.lastIndexOf('/')) : '';
        this.expandedPaths.add(this.selectedFolder);

        let parent = this.selectedFolder;

        while (parent.includes('/')) {
            parent = parent.slice(0, parent.lastIndexOf('/'));
            this.expandedPaths.add(parent);
        }

        if (this.selectedFolder) {
            this.expandedPaths.add(this.selectedFolder);
        }

        this.renderTree();

        const url = `${this.formEditBase}/${encodeSegmentPath(slug)}`;
        await this.loadFormPanel(url);
    }

    async loadFormPanel(url) {
        const response = await fetch(url, {
            headers: panelHeaders(),
            credentials: 'same-origin',
        });

        if (!response.ok) {
            showAdminToast('Could not load segment form.', 'error');

            return;
        }

        const html = await response.text();
        this.formContent.innerHTML = html;
        this.formEmpty.classList.add('d-none');
        this.folderContext?.classList.add('d-none');
        this.formContent.classList.remove('d-none');
        this.initFormWidgets();
    }

    initFormWidgets() {
        initCodeEditors(this.formContent);
        initDynamicCodeEditors(this.formContent);
        initRichTextEditors(this.formContent);
        initMediaFinders(this.formContent);
        initUrlParameters(this.formContent);
        this.releasePanelAutoFocus();
    }

    releasePanelAutoFocus() {
        const active = document.activeElement;

        if (!(active instanceof HTMLElement) || ! this.formContent.contains(active)) {
            return;
        }

        if (active.closest('.cm-editor, .ql-editor, [contenteditable="true"]')) {
            active.blur();
        }
    }

    async saveForm(form) {
        if (form.dataset.saving === 'true') {
            return;
        }

        syncCodeEditors(this.formContent);
        syncDynamicCodeEditors(this.formContent);
        syncRichTextEditors();

        form.dataset.saving = 'true';
        const submitBtn = form.querySelector('.loom-form-actions [type="submit"]');
        const submitLabel = submitBtn?.textContent?.trim() ?? '';

        if (submitBtn) {
            submitBtn.disabled = true;
            setActionSubmitLabel(submitBtn, 'Saving…');
        }

        try {
            const response = await fetch(form.action, {
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Segments-Panel': '1',
                },
                credentials: 'same-origin',
            });

            let payload = {};

            try {
                payload = await response.json();
            } catch {
                payload = {};
            }

            if (!response.ok) {
                showAdminToast(formatValidationMessage(payload, response), 'error');

                return;
            }

            showAdminToast(payload.message || 'Changes saved successfully.', 'success');
            await this.loadTree();

            if (payload.slug) {
                await this.openEditForm(payload.slug);
            }
        } catch {
            showAdminToast('Save failed. Please try again.', 'error');
        } finally {
            form.dataset.saving = 'false';

            if (submitBtn) {
                submitBtn.disabled = false;
                setActionSubmitLabel(submitBtn, submitLabel);
            }
        }
    }

    async deleteSegment(trigger) {
        const formId = trigger.dataset.segmentsDelete;
        const form = formId ? document.getElementById(formId) : null;

        if (!form) {
            return;
        }

        const confirmed = await confirmAction({
            title: trigger.dataset.confirmTitle || 'Delete segment',
            message: trigger.dataset.confirm || 'Delete this segment?',
            confirmLabel: trigger.dataset.confirmLabel || 'Delete',
            danger: true,
        });

        if (!confirmed) {
            return;
        }

        const response = await fetch(form.action, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Segments-Panel': '1',
            },
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return;
        }

        showAdminToast(payload.message || 'Segment deleted.', 'success');
        this.clearFormPanel();
        await this.loadTree();
    }

    async createFolderIn(parentPath = '') {
        const name = await promptInput({
            title: 'New folder',
            label: 'Folder name',
            confirmLabel: 'Create folder',
            hint: parentPath === ''
                ? 'Lowercase letters, numbers, and hyphens.'
                : `Creates inside ${formatFolderPath(parentPath)}.`,
            placeholder: 'e.g. header',
            validate: validateFolderName,
        });

        if (!name) {
            return;
        }

        const slug = slugifyFolderName(name);
        const path = parentPath ? `${parentPath}/${slug}` : slug;

        const response = await fetch(this.foldersBase, {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ path }),
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return;
        }

        showAdminToast(payload.message || 'Folder created.', 'success');

        let ancestor = path;

        while (ancestor !== '') {
            this.expandedPaths.add(ancestor);

            if (! ancestor.includes('/')) {
                break;
            }

            ancestor = ancestor.slice(0, ancestor.lastIndexOf('/'));
        }

        this.expandedPaths.add('');

        await this.loadTree();
        this.selectFolder(parentPath);
    }

    async renameFolder(path, currentName) {
        const name = await promptInput({
            title: 'Rename folder',
            label: 'Folder name',
            defaultValue: currentName,
            confirmLabel: 'Rename',
            hint: 'Lowercase letters, numbers, and hyphens.',
            validate: validateFolderName,
        });

        if (!name) {
            return;
        }

        const slug = slugifyFolderName(name);
        const parent = segmentDirname(path);
        const newPath = joinPath(parent, slug);

        if (newPath === path) {
            return;
        }

        const response = await fetch(`${this.foldersBase}/${encodeSegmentPath(path)}`, {
            method: 'PUT',
            headers: jsonHeaders(),
            body: JSON.stringify({ path: newPath }),
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return;
        }

        showAdminToast(payload.message || 'Folder renamed.', 'success');

        if (this.selectedFolder === path) {
            this.selectedFolder = newPath;
        }

        if (this.selectedSegment.startsWith(`${path}/`)) {
            this.selectedSegment = `${newPath}${this.selectedSegment.slice(path.length)}`;
        }

        await this.loadTree();

        if (this.selectedSegment) {
            await this.openEditForm(this.selectedSegment);
        } else if (this.selectedFolder === newPath) {
            this.showFolderContext(newPath);
        }
    }

    nodeHasContents(node) {
        for (const child of node?.children ?? []) {
            if (child.type === 'segment') {
                return true;
            }

            if (child.type === 'folder' && this.nodeHasContents(child)) {
                return true;
            }
        }

        return false;
    }

    findFolderNode(path) {
        const root = this.treeData[0];
        const children = root?.children ?? [];

        const walk = (nodes) => {
            for (const node of nodes) {
                if (node.type === 'folder' && node.path === path) {
                    return node;
                }

                if (node.type === 'folder' && node.children?.length) {
                    const found = walk(node.children);

                    if (found) {
                        return found;
                    }
                }
            }

            return null;
        };

        return walk(children);
    }

    folderHasContents(path) {
        const node = this.findFolderNode(path);

        return node ? this.nodeHasContents(node) : false;
    }

    async deleteFolder(path, hasContents = null) {
        if (hasContents === null) {
            hasContents = this.folderHasContents(path);
        }

        const confirmed = await confirmAction({
            title: 'Delete folder',
            message: hasContents
                ? 'This folder contains files. Delete the folder and everything inside it?'
                : 'Delete this folder?',
            confirmLabel: 'Delete',
            danger: true,
        });

        if (!confirmed) {
            return;
        }

        const response = await fetch(`${this.foldersBase}/${encodeSegmentPath(path)}`, {
            method: 'DELETE',
            headers: jsonHeaders(),
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return;
        }

        showAdminToast(payload.message || 'Folder deleted.', 'success');

        const affectedFolder = this.selectedFolder === path || this.selectedFolder?.startsWith(`${path}/`);
        const affectedSegment = this.selectedSegment?.startsWith(`${path}/`);
        const affected = affectedFolder || affectedSegment;

        if (affectedFolder) {
            this.selectedFolder = null;
        }

        if (affectedSegment) {
            this.selectedSegment = null;
        }

        await this.loadTree();

        if (affected) {
            this.clearFormPanel();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('segments-explorer');

    if (root) {
        new SegmentsExplorer(root);
    }
});
