function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function dotNameToInputName(key) {
    const parts = String(key).split('.');

    if (parts.length <= 1) {
        return parts[0] ?? '';
    }

    return parts[0] + parts.slice(1).map((part) => `[${part}]`).join('');
}

function fieldTargetFromLayoutMessage(message) {
    const match = String(message).match(/"([^"]+\.[^"]+)"/);

    return match ? match[1] : null;
}

export function clearFormValidationErrors(form) {
    form.querySelectorAll('.is-invalid').forEach((element) => {
        element.classList.remove('is-invalid');
    });

    form.querySelectorAll('[data-form-validation-error]').forEach((element) => {
        element.remove();
    });
}

function markFieldInvalid(form, inputName) {
    const escapedName = inputName.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    const field = form.querySelector(`[name="${escapedName}"]`);

    if (field) {
        field.classList.add('is-invalid');
    }
}

function applyLayoutFieldsValidationErrors(form, messages) {
    const container = form.querySelector('[data-page-layout-fields]');

    if (! container) {
        return;
    }

    const contentEl = container.querySelector('[data-page-layout-fields-content]');

    if (! contentEl) {
        return;
    }

    let alertEl = container.querySelector('[data-layout-fields-errors]');

    if (! alertEl) {
        alertEl = document.createElement('div');
        alertEl.className = 'alert alert-danger py-2 small mb-3';
        alertEl.setAttribute('data-layout-fields-errors', '');
        alertEl.setAttribute('data-form-validation-error', '');
        alertEl.setAttribute('role', 'alert');
        contentEl.prepend(alertEl);
    }

    alertEl.innerHTML = `<ul class="mb-0 ps-3">${messages.map((message) => `<li>${escapeHtml(message)}</li>`).join('')}</ul>`;

    const accordionPanel = container.closest('.accordion-collapse');

    if (accordionPanel) {
        accordionPanel.classList.add('show');
    }

    const accordionButton = container.closest('.accordion-item')?.querySelector('.accordion-button');

    if (accordionButton) {
        accordionButton.classList.remove('collapsed');
        accordionButton.setAttribute('aria-expanded', 'true');
    }

    messages.forEach((message) => {
        const target = fieldTargetFromLayoutMessage(message);

        if (! target) {
            return;
        }

        const [segmentPath, fieldName] = target.split('.', 2);
        const row = container.querySelector(`[data-layout-segment="${segmentPath}"] [data-layout-field="${fieldName}"]`);

        if (! row) {
            return;
        }

        row.classList.add('table-danger');
        row.querySelectorAll('select, input, textarea').forEach((element) => {
            element.classList.add('is-invalid');
        });

        const activeTab = container.querySelector(`[data-layout-segment="${segmentPath}"]`);

        if (activeTab) {
            const tabId = activeTab.getAttribute('aria-labelledby');
            const tabButton = tabId ? container.querySelector(`#${tabId}`) : null;

            if (tabButton) {
                tabButton.click();
            }
        }
    });

    alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

export function applyFormValidationErrors(form, errors) {
    if (!(form instanceof HTMLFormElement) || ! errors || typeof errors !== 'object') {
        return;
    }

    clearFormValidationErrors(form);

    Object.entries(errors).forEach(([key, messages]) => {
        const messageList = Array.isArray(messages) ? messages : [String(messages)];

        if (messageList.length === 0) {
            return;
        }

        if (key === 'layout_fields') {
            applyLayoutFieldsValidationErrors(form, messageList);

            return;
        }

        const inputName = dotNameToInputName(key);

        messageList.forEach((message) => {
            markFieldInvalid(form, inputName);

            const field = form.querySelector(`[name="${inputName.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"]`);
            const wrapper = field?.closest('.loom-form-field, .mb-3, td');

            if (! wrapper || wrapper.querySelector('[data-form-validation-error]')) {
                return;
            }

            const errorEl = document.createElement('div');
            errorEl.className = 'invalid-feedback d-block';
            errorEl.setAttribute('data-form-validation-error', '');
            errorEl.textContent = message;
            wrapper.appendChild(errorEl);
        });
    });
}
