const COMPLEX_PARAMETER_TYPES = new Set([
    'media_selector',
    'media_attach',
    'media_parameter',
    'repeater',
    'block_repeater',
    'url_parameter',
    'dynamic_code',
]);

function escapePhpSingleQuoted(value) {
    return String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function formatPhpValue(parameter) {
    const type = parameter?.type ?? 'text';
    const value = parameter?.default;

    if (COMPLEX_PARAMETER_TYPES.has(type)) {
        if (Array.isArray(value)) {
            return '[]';
        }

        return "''";
    }

    if (value === null || value === undefined) {
        return "''";
    }

    if (typeof value === 'boolean') {
        return value ? 'true' : 'false';
    }

    if (typeof value === 'number') {
        return Number.isFinite(value) ? String(value) : "''";
    }

    if (typeof value === 'object') {
        return '[]';
    }

    return `'${escapePhpSingleQuoted(value)}'`;
}

function formatPhpAssocArray(parameters) {
    if (!Array.isArray(parameters) || parameters.length === 0) {
        return '[]';
    }

    const pairs = parameters
        .filter((parameter) => parameter?.name)
        .map((parameter) => `'${escapePhpSingleQuoted(parameter.name)}' => ${formatPhpValue(parameter)}`);

    return `[${pairs.join(', ')}]`;
}

function buildSegmentSnippet(path, parameters) {
    const args = formatPhpAssocArray(parameters);

    return `@segment('${escapePhpSingleQuoted(path)}', ${args})`;
}

function segmentBasename(slug) {
    return slug.includes('/') ? slug.slice(slug.lastIndexOf('/') + 1) : slug;
}

class LayoutSegmentPicker {
    constructor(root, editorMount) {
        this.root = root;
        this.editorMount = editorMount;
        this.treeEl = root.querySelector('#layout-segments-tree');
        this.treeUrl = root.dataset.treeUrl;
        this.treeData = [];
        this.expandedPaths = new Set(['']);

        this.bindEditorDrop();
        this.loadTree();
    }

    get editorView() {
        return this.editorMount?.editorView ?? null;
    }

    bindEditorDrop() {
        const attach = () => {
            const editor = this.editorView;

            if (!editor) {
                requestAnimationFrame(attach);

                return;
            }

            const dom = editor.dom;

            dom.addEventListener('dragover', (event) => {
                if (!event.dataTransfer?.types.includes('application/json')) {
                    return;
                }

                event.preventDefault();
                event.dataTransfer.dropEffect = 'copy';
                dom.classList.add('is-drop-target');
            });

            dom.addEventListener('dragleave', (event) => {
                if (!dom.contains(event.relatedTarget)) {
                    dom.classList.remove('is-drop-target');
                }
            });

            dom.addEventListener('drop', (event) => {
                event.preventDefault();
                dom.classList.remove('is-drop-target');

                const payload = this.readDragPayload(event);

                if (!payload) {
                    return;
                }

                const pos = editor.posAtCoords({ x: event.clientX, y: event.clientY });

                if (pos === null) {
                    return;
                }

                this.insertSnippet(payload, pos);
            });
        };

        attach();
    }

    readDragPayload(event) {
        const raw = event.dataTransfer?.getData('application/json');

        if (!raw) {
            return null;
        }

        try {
            const payload = JSON.parse(raw);

            if (!payload?.path) {
                return null;
            }

            return payload;
        } catch {
            return null;
        }
    }

    async loadTree() {
        if (!this.treeUrl || !this.treeEl) {
            return;
        }

        try {
            const response = await fetch(this.treeUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            this.treeData = payload.tree ?? [];
            this.renderTree();
        } catch {
            this.treeEl.innerHTML = '<li class="text-muted small p-2">Could not load segments.</li>';
        }
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

        const label = document.createElement('span');
        label.className = 'segments-tree-node-label';
        label.textContent = 'segments';
        row.appendChild(label);

        row.addEventListener('click', () => {
            this.toggleFolder('');
        });

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
                this.toggleFolder(node.path);
            });
        } else if (node.type === 'segment') {
            row.classList.add('segments-tree-node--draggable');
            row.draggable = true;

            const spacer = document.createElement('span');
            spacer.className = 'segments-tree-toggle';
            spacer.setAttribute('aria-hidden', 'true');
            row.appendChild(spacer);

            const icon = document.createElement('i');
            icon.className = 'bi bi-file-earmark-code segments-tree-icon';
            row.appendChild(icon);

            const payload = {
                path: node.slug ?? node.path,
                parameters: node.parameters ?? [],
            };

            row.addEventListener('dragstart', (event) => {
                row.classList.add('is-dragging');
                event.dataTransfer?.setData('application/json', JSON.stringify(payload));
                event.dataTransfer.effectAllowed = 'copy';
            });

            row.addEventListener('dragend', () => {
                row.classList.remove('is-dragging');
            });

            row.addEventListener('click', () => {
                this.insertSnippet(payload);
            });
        }

        const label = document.createElement('span');
        label.className = 'segments-tree-node-label';
        label.textContent = node.name ?? segmentBasename(node.path ?? '');
        row.appendChild(label);

        li.appendChild(row);

        if (node.type === 'folder' && node.children?.length && this.expandedPaths.has(node.path)) {
            li.appendChild(this.renderBranch(node.children));
        }

        return li;
    }

    toggleFolder(path) {
        if (this.expandedPaths.has(path)) {
            this.expandedPaths.delete(path);
        } else {
            this.expandedPaths.add(path);
        }

        this.renderTree();
    }

    insertSnippet(payload, position = null) {
        const editor = this.editorView;

        if (!editor || !payload?.path) {
            return;
        }

        const snippet = buildSegmentSnippet(payload.path, payload.parameters ?? []);
        const selection = editor.state.selection.main;
        const from = position ?? selection.from;
        const to = position ?? selection.to;

        editor.dispatch({
            changes: {
                from,
                to,
                insert: snippet,
            },
            selection: {
                anchor: from + snippet.length,
            },
        });

        editor.focus();
    }
}

export function initLayoutForm() {
    const root = document.getElementById('layout-form-explorer');

    if (!root || root.dataset.initialized === 'true') {
        return;
    }

    const editorMount = document.querySelector('[data-code-editor][data-target="field-code"]');

    if (!editorMount) {
        return;
    }

    root.dataset.initialized = 'true';
    new LayoutSegmentPicker(root, editorMount);
}
