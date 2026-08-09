import test from 'node:test';
import assert from 'node:assert/strict';

const listeners = [];
globalThis.window = { printCalls: 0, print() { this.printCalls += 1; } };
globalThis.document = { querySelectorAll(selector) { assert.equal(selector, '[data-print-report]'); return [{ dataset: {}, addEventListener(type, callback) { listeners.push([type, callback]); } }]; } };

const { initializeRmAuditReportPrint } = await import('../../public/assets/js/rm-audit-report.js');

test('print control initializes once and invokes browser printing without submission', () => {
  const button = { dataset: {}, addEventListener(type, callback) { listeners.push([type, callback]); } };
  const root = { querySelectorAll: () => [button] };
  initializeRmAuditReportPrint(root);
  initializeRmAuditReportPrint(root);
  const callbacks = listeners.filter(([type]) => type === 'click').map(([, callback]) => callback);
  assert.equal(callbacks.length, 2);
  callbacks.at(-1)();
  assert.equal(window.printCalls, 1);
  assert.equal(button.dataset.printReady, 'true');
});
