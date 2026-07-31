const initializedInputs = new WeakSet();

export function formatUsPhone(value) {
    let digits = String(value ?? '').replace(/\D/g, '');
    if (digits.length === 11 && digits.startsWith('1')) digits = digits.slice(1);
    if (digits.length > 10) return null;
    if (digits.length === 0) return '';
    if (digits.length < 4) return `(${digits}`;
    if (digits.length < 7) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
}

function cursorForDigitCount(value, digitCount) {
    if (digitCount <= 0) return 0;
    let seen = 0;
    for (let index = 0; index < value.length; index += 1) {
        if (/\d/.test(value[index])) seen += 1;
        if (seen === digitCount) return index + 1;
    }
    return value.length;
}

function attachPhoneMask(input) {
    if (initializedInputs.has(input)) return;
    const initial = formatUsPhone(input.value);
    if (initial !== null) input.value = initial;
    input.dataset.phoneMaskValue = input.value;
    input.addEventListener('input', () => {
        const selection = input.selectionStart ?? input.value.length;
        const digitsBeforeCursor = input.value.slice(0, selection).replace(/\D/g, '').length;
        const formatted = formatUsPhone(input.value);
        if (formatted === null) {
            input.value = input.dataset.phoneMaskValue ?? '';
            return;
        }
        input.value = formatted;
        input.dataset.phoneMaskValue = formatted;
        const cursor = cursorForDigitCount(formatted, digitsBeforeCursor);
        input.setSelectionRange?.(cursor, cursor);
    });
    initializedInputs.add(input);
}

export function initPhoneMasks(root = document) {
    root.querySelectorAll?.('[data-phone-mask]').forEach(attachPhoneMask);
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initPhoneMasks());
    else initPhoneMasks();
}
