(() => {
  const dialog = document.querySelector('[data-dashboard-messages]');
  const opener = document.querySelector('[data-dashboard-messages-open]');
  if (!dialog || !opener) return;

  opener.addEventListener('click', () => dialog.showModal());
  dialog.querySelectorAll('[data-dashboard-messages-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
})();
