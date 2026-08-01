const initializedTargets = new WeakSet();

export function normalizeUsernameSuggestion(value) {
    return String(value ?? '').trim().toLowerCase().replace(/[^a-z0-9]/g, '').replace(/^[^a-z]+/, '').slice(0, 50);
}

function attachUsernameSuggestion(source, target) {
    if (initializedTargets.has(target)) return;
    let userControlled = target.hasAttribute('data-username-preserved') || target.value !== '';
    const suggest = () => {
        if (!userControlled) target.value = normalizeUsernameSuggestion(source.value);
    };
    target.addEventListener('input', () => { userControlled = true; });
    source.addEventListener('input', suggest);
    initializedTargets.add(target);
}

export function initUsernameSuggestions(root = document) {
    root.querySelectorAll?.('[data-username-source]').forEach((source) => {
        const target = (source.form ?? root).querySelector?.('[data-username-target]');
        if (target) attachUsernameSuggestion(source, target);
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initUsernameSuggestions(), { once: true });
    else initUsernameSuggestions();
}
