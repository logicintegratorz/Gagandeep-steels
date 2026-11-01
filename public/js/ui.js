(() => {
  'use strict';
  const region = document.querySelector('.toast-region');
  const wireToast = toast => {
    const close = toast.querySelector('.toast-close');
    close.hidden = false;
    let timer;
    const expiresAt = Date.now() + 5000;
    const checkExpiry = () => { if (Date.now() >= expiresAt) remove(); };
    const remove = () => {
      clearTimeout(timer);
      document.removeEventListener('visibilitychange', checkExpiry);
      if (toast.contains(document.activeElement)) region?.focus();
      toast.remove();
    };
    close.addEventListener('click', remove);
    document.addEventListener('visibilitychange', checkExpiry);
    timer = setTimeout(remove, 5000);
  };
  document.querySelectorAll('[data-toast]').forEach(wireToast);
  window.crmToast = (message, type = 'status') => {
    if (!region || !message) return;
    // Repeated network or validation events should not flood the screen.
    if (Array.from(region.querySelectorAll('p')).some(p => p.textContent === message)) return;
    const toast = document.createElement('div');
    const kind = ['success', 'warning', 'error', 'status'].includes(type) ? type : 'status';
    toast.className = 'toast toast-' + kind;
    toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    const content = document.createElement('div');
    const title = document.createElement('strong');
    title.textContent = { success: 'Success', warning: 'Attention', error: 'Unable to complete', status: 'Notice' }[kind];
    const text = document.createElement('p');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button'; close.className = 'toast-close'; close.textContent = '×';
    close.setAttribute('aria-label', 'Dismiss notification');
    content.append(title, text); toast.append(content, close); region.append(toast); wireToast(toast);
  };

  document.querySelectorAll('input[type="password"]').forEach((input, index) => {
    input.id ||= 'password-field-' + index;
    const wrapper = document.createElement('span');
    wrapper.className = 'password-field';
    input.before(wrapper); wrapper.append(input);
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'password-toggle';
    button.setAttribute('aria-controls', input.id);
    const fieldName = input.name.replaceAll('_', ' ');
    const update = visible => {
      input.type = visible ? 'text' : 'password';
      button.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + fieldName);
      button.setAttribute('aria-pressed', String(visible));
      button.title = visible ? 'Hide password' : 'Show password';
      button.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>' + (visible ? '<path d="m3 3 18 18"/>' : '') + '</svg>';
    };
    button.addEventListener('click', event => { event.preventDefault(); update(input.type === 'password'); });
    document.addEventListener('visibilitychange', () => { if (document.hidden) update(false); });
    input.form?.addEventListener('submit', () => update(false));
    update(false); wrapper.append(button);
  });

  let validationPending = false;
  document.addEventListener('invalid', event => {
    if (validationPending) return;
    validationPending = true;
    const field = event.target;
    const label = (field.labels?.[0]?.childNodes[0]?.textContent || field.getAttribute('aria-label') || field.name || 'Field').trim();
    window.crmToast(label + ': ' + field.validationMessage, 'error');
    setTimeout(() => { validationPending = false; }, 100);
  }, true);
  window.addEventListener('offline', () => window.crmToast('Your connection is offline. Changes cannot be saved until the connection returns.', 'warning'));
  window.addEventListener('online', () => window.crmToast('Connection restored. You can try your action again.', 'success'));
  document.querySelectorAll('[data-csv-export]').forEach(link => {
    let busy = false;
    link.addEventListener('click', async event => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      if (busy) return;
      busy = true; link.setAttribute('aria-busy', 'true');
      try {
        const response = await fetch(link.href, { headers: { Accept: 'application/json' } });
        if (!response.ok || response.redirected) {
          let message = 'The CSV export could not be completed. Please try again.';
          if (response.status === 401 || response.status === 419 || response.redirected) message = 'Your session expired. Sign in again before exporting.';
          else if (response.status === 403) message = 'Your role does not have permission to export freight rates.';
          else if (response.status === 422) {
            const data = await response.json();
            message = Object.values(data.errors || {}).flat().join(' ') || data.message || message;
          }
          throw new Error(message);
        }
        if (!response.headers.get('content-type')?.includes('text/csv')) throw new Error('The server did not return a CSV file. Please try again.');
        const url = URL.createObjectURL(await response.blob());
        const download = document.createElement('a'); download.href = url;
        download.download = 'freight-rates.csv'; document.body.append(download); download.click(); download.remove();
        setTimeout(() => URL.revokeObjectURL(url), 60000);
        window.crmToast('Freight rates CSV prepared. Your browser download has started.', 'success');
      } catch (error) {
        window.crmToast(error instanceof TypeError ? 'Could not reach the server. Check your connection and try exporting again.' : error.message, 'error');
      } finally { busy = false; link.removeAttribute('aria-busy'); }
    });
  });
  // This message is informational only; the server independently enforces expiry.
  try {
    const message = sessionStorage.getItem('crm-session-notice');
    if (message) { sessionStorage.removeItem('crm-session-notice'); window.crmToast(message, 'warning'); }
  } catch { /* Storage may be unavailable in private browsing. */ }
})();
