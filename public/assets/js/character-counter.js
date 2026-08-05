const initializedCounters = new WeakSet();

export function characterCount(value) {
    return Array.from(String(value ?? '')).length;
}

function attachCharacterCounter(textarea, root) {
    if (initializedCounters.has(textarea)) return;
    const targetId = textarea.dataset.characterCounterTarget ?? '';
    const maximum = Number.parseInt(textarea.dataset.characterCounterMax ?? '', 10);
    const counter = targetId === '' ? null : root.getElementById?.(targetId);
    if (!counter || !Number.isInteger(maximum) || maximum < 1) return;
    const update = () => { counter.textContent = `${characterCount(textarea.value)} / ${maximum}`; };
    textarea.addEventListener('input', update);
    textarea.addEventListener('change', update);
    update();
    initializedCounters.add(textarea);
}

export function initCharacterCounters(root = document) {
    root.querySelectorAll?.('[data-character-counter-target][data-character-counter-max]').forEach((textarea) => attachCharacterCounter(textarea, root));
}

if (typeof document !== 'undefined') {
    const initialize = () => initCharacterCounters();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
    globalThis.addEventListener?.('pageshow', initialize);
}
