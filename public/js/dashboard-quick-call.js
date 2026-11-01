document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.querySelector('[data-quick-call-dialog]');
  const trigger = document.querySelector('[data-quick-call-open]');
  if (!dialog || !trigger) return;

  const input = dialog.querySelector('[data-quick-call-number]');
  const actions = dialog.querySelector('[data-quick-call-actions]');
  const leadLink = dialog.querySelector('[data-quick-call-lead]');
  const status = dialog.querySelector('[data-quick-call-status]');

  const reset = () => {
    input.value = '';
    input.setCustomValidity('');
    actions.hidden = true;
    status.textContent = '';
    leadLink.href = dialog.dataset.leadCreateUrl;
  };

  const normalizedNumber = () => {
    const number = input.value.trim().replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '');
    const digitCount = number.replace(/\D/g, '').length;
    input.setCustomValidity(digitCount >= 7 && digitCount <= 15 ? '' : 'Enter a valid mobile number with 7 to 15 digits.');
    return number;
  };

  trigger.addEventListener('click', () => {
    reset();
    dialog.showModal();
    requestAnimationFrame(() => input.focus());
  });

  dialog.querySelectorAll('[data-dial-key]').forEach(button => button.addEventListener('click', () => {
    const key = button.dataset.dialKey;
    if (key === '⌫') input.value = input.value.slice(0, -1);
    else if (key === '+' && input.value.length) return;
    else input.value += key;
    input.setCustomValidity('');
    input.focus();
  }));

  dialog.querySelector('[data-quick-call-start]').addEventListener('click', () => {
    const number = normalizedNumber();
    if (!input.reportValidity()) return;
    const url = new URL(dialog.dataset.leadCreateUrl, location.href);
    url.searchParams.set('phone', input.value.trim());
    leadLink.href = url.toString();
    actions.hidden = false;
    status.textContent = 'Call opened. Abort this action or create a lead for this number.';
    window.location.href = `tel:${number}`;
  });

  input.addEventListener('input', () => {
    input.setCustomValidity('');
    actions.hidden = true;
    status.textContent = '';
  });
  dialog.querySelector('[data-quick-call-abort]').addEventListener('click', () => { dialog.close(); reset(); trigger.focus(); });
  dialog.querySelector('[data-quick-call-close]').addEventListener('click', () => { dialog.close(); reset(); trigger.focus(); });
  dialog.addEventListener('close', reset);
});
