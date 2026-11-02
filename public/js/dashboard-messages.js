(() => {
  const dialog = document.querySelector('[data-dashboard-messages]');
  const opener = document.querySelector('[data-dashboard-messages-open]');
  if (!dialog || !opener) return;

  opener.addEventListener('click', () => dialog.showModal());
  dialog.querySelectorAll('[data-dashboard-messages-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', event => {
    if (event.target === dialog) {
      dialog.close();
      return;
    }
    dialog.querySelectorAll('details[open]').forEach(panel => {
      if (!panel.contains(event.target)) panel.removeAttribute('open');
    });
  });

  const recipientForm = dialog.querySelector('[data-message-recipient-form]');
  if (!recipientForm) return;
  const allUsers = recipientForm.querySelector('[data-message-all-users]');
  const recipients = [...recipientForm.querySelectorAll('[data-message-recipient]')];
  const audience = recipientForm.querySelector('[data-message-audience]');
  const summary = recipientForm.querySelector('[data-message-recipient-summary]');
  const syncRecipients = () => {
    if (allUsers.checked) recipients.forEach(input => { input.checked = true; });
    const selected = recipients.filter(input => input.checked);
    audience.value = allUsers.checked ? 'all' : 'users';
    summary.textContent = allUsers.checked ? 'All active users' : `${selected.length} user${selected.length === 1 ? '' : 's'} selected`;
  };
  allUsers.addEventListener('change', () => {
    recipients.forEach(input => { input.checked = allUsers.checked; });
    syncRecipients();
  });
  recipients.forEach(input => input.addEventListener('change', () => {
    allUsers.checked = false;
    if (!recipients.some(option => option.checked)) allUsers.checked = true;
    syncRecipients();
  }));
  syncRecipients();
})();
