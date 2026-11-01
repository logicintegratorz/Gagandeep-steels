(() => {
  'use strict';
  const dialog = document.querySelector('#calculator-dialog');
  const display = dialog?.querySelector('[data-calculator-display]');
  const opener = document.querySelector('[data-calculator-open]');
  if (!dialog || !display || !opener) return;

  let current = '0';
  let stored = null;
  let operator = null;
  let waiting = false;

  const show = value => {
    current = value;
    display.textContent = value.length > 16 ? Number(value).toExponential(8) : value;
  };
  const normalise = value => {
    if (!Number.isFinite(value)) return 'Error';
    return String(Number.parseFloat(value.toPrecision(12)));
  };
  const reset = () => { stored = null; operator = null; waiting = false; show('0'); };
  const calculate = () => {
    if (stored === null || !operator || current === 'Error') return;
    const left = Number(stored), right = Number(current);
    const result = operator === '+' ? left + right : operator === '-' ? left - right : operator === '*' ? left * right : right === 0 ? NaN : left / right;
    show(normalise(result)); stored = null; operator = null; waiting = true;
  };
  const chooseOperator = next => {
    if (current === 'Error') reset();
    if (operator && !waiting) calculate();
    stored = current; operator = next; waiting = true;
  };
  const digit = value => {
    if (current === 'Error' || waiting) { show(value); waiting = false; return; }
    show(current === '0' ? value : current + value);
  };
  const decimal = () => {
    if (current === 'Error' || waiting) { show('0.'); waiting = false; }
    else if (!current.includes('.')) show(current + '.');
  };

  opener.addEventListener('click', () => { dialog.showModal(); dialog.querySelector('[data-calculator-digit="7"]')?.focus(); });
  dialog.querySelector('[data-calculator-close]')?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  dialog.querySelectorAll('[data-calculator-digit]').forEach(button => button.addEventListener('click', () => digit(button.dataset.calculatorDigit)));
  dialog.querySelectorAll('[data-calculator-operator]').forEach(button => button.addEventListener('click', () => chooseOperator(button.dataset.calculatorOperator)));
  dialog.querySelector('[data-calculator-action="clear"]')?.addEventListener('click', reset);
  dialog.querySelector('[data-calculator-action="equals"]')?.addEventListener('click', calculate);
  dialog.querySelector('[data-calculator-action="decimal"]')?.addEventListener('click', decimal);
  dialog.querySelector('[data-calculator-action="negate"]')?.addEventListener('click', () => { if (current !== '0' && current !== 'Error') show(current.startsWith('-') ? current.slice(1) : '-' + current); });
  dialog.querySelector('[data-calculator-action="percent"]')?.addEventListener('click', () => { if (current !== 'Error') show(normalise(Number(current) / 100)); });
  dialog.querySelector('[data-calculator-action="backspace"]')?.addEventListener('click', () => { if (!waiting && current !== 'Error') show(current.length > 1 ? current.slice(0, -1) : '0'); });

  document.addEventListener('keydown', event => {
    if (!dialog.open) return;
    if (/^[0-9]$/.test(event.key)) { event.preventDefault(); digit(event.key); }
    else if (['+', '-', '*', '/'].includes(event.key)) { event.preventDefault(); chooseOperator(event.key); }
    else if (event.key === '.') { event.preventDefault(); decimal(); }
    else if (event.key === 'Enter' || event.key === '=') { event.preventDefault(); calculate(); }
    else if (event.key === 'Backspace') { event.preventDefault(); dialog.querySelector('[data-calculator-action="backspace"]')?.click(); }
    else if (event.key === 'Delete') { event.preventDefault(); reset(); }
  });
})();
