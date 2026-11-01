const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

let now = 0, nextId = 0;
const timers = new Map();
const toastTypes = ['success', 'status', 'warning', 'error'];
const toasts = toastTypes.map(type => {
  const close = { hidden: true, addEventListener() {} };
  return { type, removed: false, querySelector: () => close, contains: () => false, remove() { this.removed = true; } };
});
const document = {
  querySelector: () => ({ focus() {} }),
  querySelectorAll: selector => selector === '[data-toast]' ? toasts : [],
  addEventListener() {}, removeEventListener() {},
};
const context = {
  document, window: { addEventListener() {} }, sessionStorage: { getItem: () => null },
  Date: { now: () => now },
  setTimeout: (callback, delay) => { const id = ++nextId; timers.set(id, { callback, at: now + delay }); return id; },
  clearTimeout: id => timers.delete(id),
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/ui.js'), 'utf8'), context);
const advance = milliseconds => {
  now += milliseconds;
  for (const [id, timer] of timers) if (timer.at <= now) { timers.delete(id); timer.callback(); }
};
advance(4999);
for (const toast of toasts) assert.equal(toast.removed, false, `${toast.type} must remain before five seconds`);
advance(1);
for (const toast of toasts) assert.equal(toast.removed, true, `${toast.type} must close after five seconds`);
assert.equal(timers.size, 0);
console.log('PASS: success, notice, warning and error toasts all auto-close at 5 seconds.');
