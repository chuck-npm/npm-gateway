import test from 'node:test';
import assert from 'node:assert/strict';
import { characterCount, initCharacterCounters } from '../../public/assets/js/character-counter.js';

class FakeCounter { constructor() { this.textContent = ''; } }
class FakeTextarea {
    constructor(value = '', marked = true) { this.value = value;this.marked = marked;this.dataset = { characterCounterTarget:'comments-count',characterCounterMax:'5000' };this.listeners = {}; }
    addEventListener(type, listener) { (this.listeners[type] ??= []).push(listener); }
    enter(value, event = 'input') { this.value = value;(this.listeners[event] ?? []).forEach((listener) => listener()); }
}
class FakeRoot {
    constructor(textareas, counter = new FakeCounter()) { this.textareas = textareas;this.counter = counter; }
    querySelectorAll(selector) { return selector === '[data-character-counter-target][data-character-counter-max]' ? this.textareas.filter((item) => item.marked) : []; }
    getElementById(id) { return id === 'comments-count' ? this.counter : null; }
}

test('counts Unicode code points like the server character limit', () => { assert.equal(characterCount(''),0);assert.equal(characterCount('abc'),3);assert.equal(characterCount('A😀B'),3); });
test('initializes from restored content and communicates the approved maximum', () => { const textarea=new FakeTextarea('Restored value');const root=new FakeRoot([textarea]);initCharacterCounters(root);assert.equal(root.counter.textContent,'14 / 5000'); });
test('updates for typing, paste, deletion, and selected-text replacement input events', () => { const textarea=new FakeTextarea();const root=new FakeRoot([textarea]);initCharacterCounters(root);for(const [value,expected] of [['typed','5 / 5000'],['pasted content','14 / 5000'],['paste','5 / 5000'],['replacement','11 / 5000']]){textarea.enter(value);assert.equal(root.counter.textContent,expected);} });
test('change updates autofill-like changes and reinitialization adds no duplicate listeners', () => { const textarea=new FakeTextarea('initial');const root=new FakeRoot([textarea]);initCharacterCounters(root);initCharacterCounters(root);assert.equal(textarea.listeners.input.length,1);textarea.enter('autofilled','change');assert.equal(root.counter.textContent,'10 / 5000'); });
test('missing targets and invalid maxima fail safely', () => { const textarea=new FakeTextarea('safe');textarea.dataset.characterCounterMax='invalid';const root=new FakeRoot([textarea]);assert.doesNotThrow(()=>initCharacterCounters(root));assert.equal(root.counter.textContent,''); });
