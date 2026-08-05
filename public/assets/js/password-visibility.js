export function initializePasswordVisibility(root = document) {
    root.querySelectorAll('[data-password-visibility-control]').forEach((control) => {
        if (control.dataset.passwordVisibilityInitialized === 'true') return;
        const target = root.getElementById(control.getAttribute('aria-controls'));
        if (!(target instanceof HTMLInputElement)) return;
        control.checked = false;
        target.type = 'password';
        control.dataset.passwordVisibilityInitialized = 'true';
        control.addEventListener('change', () => { target.type = control.checked ? 'text' : 'password'; });
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initializePasswordVisibility(), { once: true });
else initializePasswordVisibility();
