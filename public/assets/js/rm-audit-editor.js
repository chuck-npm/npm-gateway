const initializedForms = new WeakSet();

export function hasMeaningfulAuditFindings(quill) {
    return String(quill?.getText?.() ?? '').replace(/\u00a0/g, ' ').trim().length > 0;
}

export function synchronizeAuditFindings(quill, field) {
    const meaningful = hasMeaningfulAuditFindings(quill);
    field.value = meaningful ? quill.getSemanticHTML() : '';
    return meaningful;
}

export function initializeRmAuditEditor(root = document, QuillClass = globalThis.Quill) {
    const form = root.querySelector?.('[data-rm-audit-editor]');
    if (!form || !QuillClass || initializedForms.has(form)) return null;
    const field = form.querySelector('#audit_findings_html');
    const host = form.querySelector('#rm-audit-editor');
    const error = form.querySelector('[data-rm-audit-findings-error]');
    if (!field || !host) return null;

    host.hidden = false;
    const quill = new QuillClass(host, {
        theme: 'snow',
        modules: { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],[{indent:'-1'},{indent:'+1'}],['link','clean']] },
        formats: ['bold','italic','underline','list','indent','link'],
    });
    quill.clipboard.dangerouslyPasteHTML(field.value || '');
    quill.root.setAttribute('aria-label', 'Audit Findings');
    quill.root.setAttribute('aria-required', 'true');
    quill.root.setAttribute('aria-describedby', 'audit-findings-help audit_findings_html_error');

    field.required = false;
    field.removeAttribute('required');
    field.classList.add('visually-hidden');
    field.setAttribute('aria-hidden', 'true');
    field.tabIndex = -1;

    const showRequiredError = () => {
        quill.root.setAttribute('aria-invalid', 'true');
        if (error) { error.hidden = false;error.textContent = 'Audit Findings are required.'; }
    };
    const clearRequiredError = () => {
        quill.root.removeAttribute('aria-invalid');
        if (error) error.hidden = true;
    };
    const synchronize = () => {
        const meaningful = synchronizeAuditFindings(quill, field);
        if (meaningful) clearRequiredError();
        return meaningful;
    };

    quill.on('text-change', synchronize);
    quill.root.addEventListener('paste', (event) => {
        for (const item of event.clipboardData?.items || []) if (item.type.startsWith('image/')) event.preventDefault();
    });
    form.addEventListener('submit', (event) => {
        if (synchronize()) return;
        event.preventDefault();
        showRequiredError();
        quill.focus();
    }, { capture: true });
    synchronizeAuditFindings(quill, field);
    if (error && !error.hidden) quill.root.setAttribute('aria-invalid', 'true');
    initializedForms.add(form);
    return { quill, field, synchronize };
}

if (typeof document !== 'undefined') {
    const initialize = () => initializeRmAuditEditor();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
}
