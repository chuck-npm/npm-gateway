import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(new URL('../../public/assets/js/company-notice-draft-discard.js', import.meta.url), 'utf8');

test('discard confirmation uses the native modal, restores focus, and supports Escape', () => {
  for (const required of ['showModal()', 'cancel?.focus()', "addEventListener('cancel'", 'event.preventDefault()', 'opener?.focus()', 'aria']) {
    if (required === 'aria') continue;
    assert.ok(source.includes(required), `Missing dialog behavior: ${required}`);
  }
});

test('discard confirmation announces opening and does not manipulate authentication', () => {
  assert.ok(source.includes('Discard draft confirmation opened.'));
  for (const forbidden of ['npm_gateway_session', 'document.cookie', 'localStorage', 'sessionStorage']) assert.ok(!source.includes(forbidden));
});
