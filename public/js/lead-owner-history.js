document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.getElementById('lead-owner-history');
  if (!dialog) return;
  const body = dialog.querySelector('[data-lead-history-body]');
  let url = '';
  async function load(user = '') {
    const target = new URL(url, location.href);
    if (user) target.searchParams.set('user', user);
    body.innerHTML = '<p>Loading history…</p>';
    try {
      const response = await fetch(target, { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' });
      if (!response.ok) throw new Error('History could not be loaded. Please try again.');
      body.innerHTML = await response.text();
    } catch (error) { body.textContent = error.message; }
  }
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-lead-history-url]');
    if (trigger) { event.preventDefault(); url = trigger.dataset.leadHistoryUrl; dialog.showModal(); load(); }
    if (event.target.closest('[data-lead-history-close]')) dialog.close();
  });
  dialog.addEventListener('change', (event) => {
    if (event.target.matches('[data-lead-history-user]')) load(event.target.value);
  });
  dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
});
