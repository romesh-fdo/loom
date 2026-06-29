import * as bootstrap from 'bootstrap';

const TOAST_META = {
    success: { label: 'Success', icon: 'bi-check-circle-fill', class: 'text-success' },
    error: { label: 'Error', icon: 'bi-exclamation-circle-fill', class: 'text-danger' },
    warning: { label: 'Warning', icon: 'bi-exclamation-triangle-fill', class: 'text-warning' },
    info: { label: 'Info', icon: 'bi-info-circle-fill', class: 'text-primary' },
};

let confirmModal = null;
let confirmResolve = null;

function getToastContainer() {
    let container = document.getElementById('admin-toast-container');

    if (!container) {
        container = document.createElement('div');
        container.id = 'admin-toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3 admin-flash-toasts';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
    }

    return container;
}

function dismissToast(toast, hideTimer) {
    if (hideTimer) {
        window.clearTimeout(hideTimer);
    }

    toast.classList.remove('show');

    window.setTimeout(() => {
        toast.remove();
    }, 300);
}

export function showAdminToast(message, type = 'info', delay = 5000) {
    const meta = TOAST_META[type] || TOAST_META.info;
    const container = getToastContainer();
    const toast = document.createElement('div');

    toast.className = 'toast show admin-flash-toast';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="toast-header">
            <i class="bi ${meta.icon} ${meta.class} me-2"></i>
            <strong class="me-auto">${meta.label}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    `;

    toast.querySelector('.toast-body').textContent = message;
    container.appendChild(toast);

    const hideTimer = window.setTimeout(() => {
        dismissToast(toast);
    }, delay);

    toast.querySelector('[data-bs-dismiss="toast"]')?.addEventListener('click', () => {
        dismissToast(toast, hideTimer);
    });

    return toast;
}

function getConfirmModal() {
    const element = document.getElementById('admin-confirm-modal');

    if (!element) {
        return null;
    }

    if (!confirmModal) {
        confirmModal = new bootstrap.Modal(element);

        element.querySelector('[data-admin-confirm-cancel]')?.addEventListener('click', () => {
            confirmModal.hide();
            confirmResolve?.(false);
            confirmResolve = null;
        });

        element.querySelector('[data-admin-confirm-accept]')?.addEventListener('click', () => {
            confirmModal.hide();
            confirmResolve?.(true);
            confirmResolve = null;
        });

        element.addEventListener('hidden.bs.modal', () => {
            if (confirmResolve) {
                confirmResolve(false);
                confirmResolve = null;
            }
        });
    }

    return confirmModal;
}

export function confirmAction({
    title = 'Confirm',
    message = 'Are you sure?',
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    danger = false,
} = {}) {
    const modal = getConfirmModal();

    if (!modal) {
        return Promise.resolve(false);
    }

    const element = document.getElementById('admin-confirm-modal');
    const titleEl = element.querySelector('[data-admin-confirm-title]');
    const messageEl = element.querySelector('[data-admin-confirm-message]');
    const acceptBtn = element.querySelector('[data-admin-confirm-accept]');
    const cancelBtn = element.querySelector('[data-admin-confirm-cancel]');

    titleEl.textContent = title;
    messageEl.textContent = message;
    acceptBtn.textContent = confirmLabel;
    cancelBtn.textContent = cancelLabel;

    acceptBtn.classList.toggle('btn-danger', danger);
    acceptBtn.classList.toggle('btn-primary', !danger);

    return new Promise((resolve) => {
        confirmResolve = resolve;
        modal.show();
    });
}

export function initAdminNotifications() {
    document.querySelectorAll('.admin-flash-toast').forEach((toast) => {
        const delay = parseInt(toast.dataset.bsDelay || '5000', 10);
        const hideTimer = window.setTimeout(() => {
            dismissToast(toast);
        }, delay);

        toast.querySelector('[data-bs-dismiss="toast"]')?.addEventListener('click', () => {
            dismissToast(toast, hideTimer);
        });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = form.dataset.confirm;

        if (!message) {
            return;
        }

        if (form.dataset.confirmed === 'true') {
            form.dataset.confirmed = '';
            return;
        }

        event.preventDefault();

        confirmAction({
            title: form.dataset.confirmTitle || 'Confirm',
            message,
            confirmLabel: form.dataset.confirmLabel || 'Confirm',
            cancelLabel: form.dataset.cancelLabel || 'Cancel',
            danger: form.dataset.confirmDanger !== 'false',
        }).then((confirmed) => {
            if (!confirmed) {
                return;
            }

            form.dataset.confirmed = 'true';
            form.requestSubmit();
        });
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm-form]');

        if (!trigger) {
            return;
        }

        event.preventDefault();

        const formId = trigger.dataset.confirmForm;
        const form = formId ? document.getElementById(formId) : null;

        if (!form) {
            return;
        }

        const message = trigger.dataset.confirm || form.dataset.confirm || 'Are you sure?';

        confirmAction({
            title: trigger.dataset.confirmTitle || form.dataset.confirmTitle || 'Confirm',
            message,
            confirmLabel: trigger.dataset.confirmLabel || form.dataset.confirmLabel || 'Confirm',
            cancelLabel: trigger.dataset.cancelLabel || form.dataset.cancelLabel || 'Cancel',
            danger: (trigger.dataset.confirmDanger ?? form.dataset.confirmDanger) !== 'false',
        }).then((confirmed) => {
            if (!confirmed) {
                return;
            }

            form.requestSubmit();
        });
    });
}
