import test from 'node:test';
import assert from 'node:assert/strict';
import { formatUsPhone, initPhoneMasks } from '../../public/assets/js/phone-mask.js';

class FakeInput {
    constructor(value = '', marked = true) { this.value = value; this.marked = marked; this.dataset = {}; this.listeners = []; this.selectionStart = value.length; }
    addEventListener(type, listener) { if (type === 'input') this.listeners.push(listener); }
    setSelectionRange(start) { this.selectionStart = start; }
    enter(value, cursor = value.length) { this.value = value; this.selectionStart = cursor; this.listeners.forEach((listener) => listener()); }
}
class FakeRoot { constructor(inputs) { this.inputs = inputs; } querySelectorAll(selector) { return selector === '[data-phone-mask]' ? this.inputs.filter((input) => input.marked) : []; } }

test('formats complete, punctuated, pasted, and leading-US-country-code values', () => {
    assert.equal(formatUsPhone('2294495184'), '(229) 449-5184');
    assert.equal(formatUsPhone('229-449-5184'), '(229) 449-5184');
    assert.equal(formatUsPhone('(229) 449-5184'), '(229) 449-5184');
    assert.equal(formatUsPhone('+1 229 449 5184'), '(229) 449-5184');
});
test('formats partial input progressively and removes non-digits', () => {
    assert.equal(formatUsPhone(''), '');assert.equal(formatUsPhone('2'), '(2');assert.equal(formatUsPhone('22'), '(22');assert.equal(formatUsPhone('229'), '(229');assert.equal(formatUsPhone('2294'), '(229) 4');assert.equal(formatUsPhone('229449'), '(229) 449');assert.equal(formatUsPhone('2294495'), '(229) 449-5');assert.equal(formatUsPhone('abc229x449y5184'), '(229) 449-5184');
});
test('rejects unsupported excess digits instead of silently truncating', () => { assert.equal(formatUsPhone('22294495184'), null); });
test('initializes marked inputs independently and leaves unmarked inputs alone', () => {
    const office = new FakeInput('2294495184');const ivr = new FakeInput('+12293544477');const zip = new FakeInput('31791', false);initPhoneMasks(new FakeRoot([office, ivr, zip]));assert.equal(office.value, '(229) 449-5184');assert.equal(ivr.value, '(229) 354-4477');assert.equal(zip.value, '31791');office.enter('7065874386');assert.equal(office.value, '(706) 587-4386');assert.equal(ivr.value, '(229) 354-4477');
});
test('reinitialization attaches no duplicate listener and no marked fields is safe', () => {
    const input = new FakeInput('2294495184');const root = new FakeRoot([input]);initPhoneMasks(root);initPhoneMasks(root);assert.equal(input.listeners.length, 1);assert.equal(input.value, '(229) 449-5184');assert.doesNotThrow(() => initPhoneMasks(new FakeRoot([])));
});
test('invalid eleventh digit preserves the last valid value', () => {
    const input = new FakeInput('2294495184');initPhoneMasks(new FakeRoot([input]));input.enter('22944951845');assert.equal(input.value, '(229) 449-5184');
});
test('backspace, delete, selection replacement, and cursor position follow the shared mask', () => {
    const input = new FakeInput('2293544477');initPhoneMasks(new FakeRoot([input]));
    input.enter('(229) 354-447', 13);assert.equal(input.value, '(229) 354-447');assert.equal(input.selectionStart, 13);
    input.enter('(229) 354447', 9);assert.equal(input.value, '(229) 354-447');assert.equal(input.selectionStart, 9);
    input.enter('(706) 354-447', 4);assert.equal(input.value, '(706) 354-447');assert.equal(input.selectionStart, 4);
});
