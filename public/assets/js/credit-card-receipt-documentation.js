export function initializeReceiptDocumentation(root = document) {
  root.querySelectorAll('[data-receipt-documentation]').forEach((section) => {
    const checkbox = section.querySelector('#receipt_missing');
    const fields = section.querySelector('[data-missing-receipt-fields]');
    const explanation = fields?.querySelector('#missing_receipt_reason');
    if (!(checkbox instanceof HTMLInputElement) || !(fields instanceof HTMLElement) || !(explanation instanceof HTMLTextAreaElement)) return;

    const synchronize = (clear = false) => {
      const expanded = checkbox.checked;
      fields.hidden = !expanded;
      explanation.disabled = !expanded;
      explanation.required = expanded;
      checkbox.setAttribute('aria-expanded', String(expanded));
      if (!expanded && clear) explanation.value = '';
    };

    checkbox.addEventListener('change', () => synchronize(true));
    synchronize(false);
  });
}

if (typeof document !== 'undefined') initializeReceiptDocumentation();
