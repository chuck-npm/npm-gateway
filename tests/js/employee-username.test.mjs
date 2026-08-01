import assert from 'node:assert/strict';
import test from 'node:test';
import { readFile } from 'node:fs/promises';

const sourceCode = await readFile(new URL('../../public/assets/js/employee-username.js', import.meta.url), 'utf8');
const { initUsernameSuggestions, normalizeUsernameSuggestion } = await import(`data:text/javascript,${encodeURIComponent(sourceCode)}`);

class Input {
    constructor(value = '', attributes = []) { this.value = value; this.attributes = new Set(attributes); this.listeners = new Map(); this.form = null; }
    addEventListener(type, listener) { const listeners = this.listeners.get(type) ?? []; listeners.push(listener); this.listeners.set(type, listeners); }
    hasAttribute(name) { return this.attributes.has(name); }
    dispatch(type) { for (const listener of this.listeners.get(type) ?? []) listener(); }
}

function fixture({ username = '', preserved = false, includeSource = true, includeTarget = true } = {}) {
    const source = includeSource ? new Input() : null;
    const target = includeTarget ? new Input(username, preserved ? ['data-username-preserved'] : []) : null;
    const form = { querySelector: (selector) => selector === '[data-username-target]' ? target : null };
    if (source) source.form = form;
    const root = { querySelectorAll: (selector) => selector === '[data-username-source]' && source ? [source] : [] };
    return { source, target, root };
}

test('normalizes approved lowercase letter and digit suggestions', () => {
    assert.equal(normalizeUsernameSuggestion('Tim'), 'tim');
    assert.equal(normalizeUsernameSuggestion('  TIM  '), 'tim');
    assert.equal(normalizeUsernameSuggestion('Mary Ann'), 'maryann');
    assert.equal(normalizeUsernameSuggestion(" O'Malley "), 'omalley');
});

test('first-name changes update only an untouched suggestion', () => {
    const { source, target, root } = fixture(); initUsernameSuggestions(root);
    assert.equal(target.value, '');
    source.value = 'Tim'; source.dispatch('input'); assert.equal(target.value, 'tim');
    source.value = 'Timothy'; source.dispatch('input'); assert.equal(target.value, 'timothy');
    target.value = 'tim.thompson'; target.dispatch('input');
    source.value = 'Thomas'; source.dispatch('input'); assert.equal(target.value, 'tim.thompson');
});

test('preserved old input is never overwritten, including an empty submission', () => {
    for (const username of ['tim.thompson', '']) {
        const { source, target, root } = fixture({ username, preserved: true }); initUsernameSuggestions(root);
        source.value = 'Timothy'; source.dispatch('input'); assert.equal(target.value, username);
    }
});

test('multiple initialization is idempotent and missing fields are harmless', () => {
    const { source, target, root } = fixture(); initUsernameSuggestions(root); initUsernameSuggestions(root);
    assert.equal(source.listeners.get('input').length, 1); assert.equal(target.listeners.get('input').length, 1);
    assert.doesNotThrow(() => initUsernameSuggestions(fixture({ includeSource: false }).root));
    assert.doesNotThrow(() => initUsernameSuggestions(fixture({ includeTarget: false }).root));
});
