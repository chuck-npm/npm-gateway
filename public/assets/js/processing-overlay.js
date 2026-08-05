const FORM_SELECTOR = '[data-processing-form]';
const OVERLAY_SELECTOR = '[data-processing-overlay]';
const FALLBACK_MESSAGE = 'Processing your request…';

function resetForm(form) {
    form.dataset.processingSubmitted = '';
    form.removeAttribute('aria-busy');
    form.querySelectorAll('button[type="submit"], input[type="submit"], input[type="image"]').forEach((control) => {
        if (control.dataset.processingWasDisabled === 'false') control.disabled = false;
        if (control.dataset.processingClickedSubmitter === 'true') control.removeAttribute('aria-disabled');
        delete control.dataset.processingWasDisabled;
        delete control.dataset.processingClickedSubmitter;
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
            const submitter = event.submitter?.form === form ? event.submitter : null;
            if (event.defaultPrevented || !form.checkValidity()) return;
            form.dataset.processingSubmitted = 'true';
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button[type="submit"], input[type="submit"], input[type="image"]').forEach((control) => {
                control.dataset.processingWasDisabled = String(control.disabled);
                if (control === submitter) {
                    control.dataset.processingClickedSubmitter = 'true';
                    control.setAttribute('aria-disabled', 'true');
                } else control.disabled = true;
            });
            queueMicrotask(() => {
                if (event.defaultPrevented) { resetForm(form); return; }
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
