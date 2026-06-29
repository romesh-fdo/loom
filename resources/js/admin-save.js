import { showAdminToast } from './admin-notifications';
import { syncDynamicCodeEditors } from './dynamic-code-editor';
import { syncRichTextEditors } from './rich-text-editor';

function getAdminResourceForm() {
    return document.querySelector('main.admin-content form:has(.loom-form-actions):not([data-no-save-shortcut]):not([data-plugin-builder-form])');
}

function formatValidationMessage(payload) {
    if (payload?.errors && typeof payload.errors === 'object') {
        const firstField = Object.keys(payload.errors)[0];
        const firstMessage = firstField ? payload.errors[firstField]?.[0] : null;

        if (firstMessage) {
            return firstMessage;
        }
    }

    return payload?.message || 'Please fix the validation errors.';
}

export async function saveAdminForm(form) {
    if (!(form instanceof HTMLFormElement) || form.dataset.saving === 'true') {
        return false;
    }

    syncDynamicCodeEditors();
    syncRichTextEditors();

    form.dataset.saving = 'true';

    const submitBtn = form.querySelector('.loom-form-actions [type="submit"]');
    const submitLabel = submitBtn?.textContent?.trim() ?? '';

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving…';
    }

    try {
        const response = await fetch(form.action, {
            method: (form.getAttribute('method') || 'POST').toUpperCase(),
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        if (! response.ok) {
            showAdminToast(formatValidationMessage(payload), 'error');

            return false;
        }

        showAdminToast(payload.message || 'Changes saved successfully.', 'success');

        if (payload.redirect) {
            window.location.href = payload.redirect;
        }

        return true;
    } catch {
        showAdminToast('Save failed. Please try again.', 'error');

        return false;
    } finally {
        form.dataset.saving = 'false';

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitLabel;
        }
    }
}

export function initAdminSaveShortcut() {
    document.addEventListener('keydown', (event) => {
        if (! (event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') {
            return;
        }

        const form = getAdminResourceForm();

        if (! form) {
            return;
        }

        event.preventDefault();
        saveAdminForm(form);
    });
}
