const FORM_SELECTOR = '[data-processing-form]';
const OVERLAY_SELECTOR = '[data-processing-overlay]';
const FALLBACK_MESSAGE = 'Processing your request…';

function resetForm(form) {
    form.dataset.processingSubmitted = '';
    form.removeAttribute('aria-busy');
    form.querySelectorAll('button[type="submit"], input[type="submit"], input[type="image"]').forEach((control) => {
        if (control.dataset.processingWasDisabled === 'false') control.disabled = false;
        delete control.dataset.processingWasDisabled;
    });
}

function hideOverlay(overlay) {
    if (!overlay) return;
    overlay.hidden = true;
    overlay.setAttribute('aria-hidden', 'true');
}

export function initializeProcessingOverlays(root = document) {
    const overlay = root.querySelector(OVERLAY_SELECTOR);
    root.querySelectorAll(FORM_SELECTOR).forEach((form) => {
        if (form.dataset.processingInitialized === 'true') return;
        form.dataset.processingInitialized = 'true';
        form.addEventListener('submit', (event) => {
            if (form.dataset.processingSubmitted === 'true') {
                event.preventDefault();
                return;
            }
            queueMicrotask(() => {
                if (event.defaultPrevented || !form.checkValidity() || form.dataset.processingSubmitted === 'true') return;
                form.dataset.processingSubmitted = 'true';
                form.setAttribute('aria-busy', 'true');
                form.querySelectorAll('button[type="submit"], input[type="submit"], input[type="image"]').forEach((control) => {
                    control.dataset.processingWasDisabled = String(control.disabled);
                    control.disabled = true;
                });
                if (!overlay) return;
                const message = overlay.querySelector('[data-processing-overlay-message]');
                if (message) message.textContent = form.dataset.processingMessage || FALLBACK_MESSAGE;
                overlay.hidden = false;
                overlay.setAttribute('aria-hidden', 'false');
            });
        });
    });
    return { reset() { root.querySelectorAll(FORM_SELECTOR).forEach(resetForm); hideOverlay(overlay); } };
}

let controller;
function initialize() { controller = initializeProcessingOverlays(document); }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
window.addEventListener('pageshow', () => controller?.reset());
