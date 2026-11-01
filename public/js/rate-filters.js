(() => {
  'use strict';
  const form = document.querySelector('.directory form.filters');
  if (!form) return;
  const directory = form.closest('.directory');
  let timer, controller, revision = 0;
  const status = document.createElement('p');
  status.className = 'live-filter-status';
  status.setAttribute('role', 'status');
  status.setAttribute('aria-live', 'polite');
  form.after(status);
  const buildUrl = () => {
    const url = new URL(form.action);
    for (const [key, value] of new FormData(form)) if (String(value).trim()) url.searchParams.set(key, value);
    return url;
  };
  const invalidate = () => {
    clearTimeout(timer); controller?.abort(); revision++;
  };
  const update = async (url = buildUrl()) => {
    invalidate();
    const current = revision;
    controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    directory.setAttribute('aria-busy', 'true');
    status.textContent = 'Updating rates…';
    try {
      const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'text/html' }, cache: 'no-store' });
      if (current !== revision) return;
      if (response.redirected && new URL(response.url).pathname === '/login') { location.assign(response.url); return; }
      if (!response.ok) throw new Error('Could not update rates. Please try changing the filter again.');
      const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
      if (current !== revision) return;
      const incoming = doc.querySelector('.directory');
      if (!incoming?.querySelector('.table-scroll')) throw new Error('Could not load the rate list. Please refresh and try again.');
      for (const selector of ['.table-scroll', '.table-footer', '.total-rate-count']) {
        const old = directory.querySelector(selector), next = incoming.querySelector(selector);
        if (old && next) old.replaceWith(next);
      }
      directory.querySelectorAll('[data-delete-trigger]').forEach(button => { button.disabled = false; });
      const exportLink = directory.querySelector('[data-csv-export]');
      const nextExport = incoming.querySelector('[data-csv-export]');
      if (exportLink && nextExport) exportLink.href = nextExport.href;
      history.replaceState(null, '', url);
      status.textContent = directory.querySelector('.table-footer>span')?.textContent.trim().replace(/\s+/g, ' ') || 'Rates updated.';
    } catch (error) {
      if (current !== revision) return;
      status.textContent = error.name === 'AbortError' ? 'Request timed out. Change a filter to try again.' : error.message;
      window.crmToast?.(status.textContent, 'error');
    } finally {
      clearTimeout(timeout);
      if (current === revision) directory.removeAttribute('aria-busy');
    }
  };
  form.addEventListener('submit', event => { event.preventDefault(); update(); });
  form.addEventListener('input', event => {
    if (!event.target.name || event.target.tagName !== 'INPUT') return;
    invalidate();
    if (event.isComposing) return;
    timer = setTimeout(update, 400);
  });
  form.addEventListener('compositionend', () => { invalidate(); timer = setTimeout(update, 400); });
  form.addEventListener('change', event => { if (event.target.tagName === 'SELECT') update(); });
  directory.addEventListener('click', event => {
    const link = event.target.closest('.pagination a');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    event.preventDefault(); update(new URL(link.href));
  });
})();
