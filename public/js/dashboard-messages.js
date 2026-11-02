(() => {
  const dialog = document.querySelector('[data-dashboard-messages]');
  const opener = document.querySelector('[data-dashboard-messages-open]');
  if (!dialog || !opener) return;

  opener.addEventListener('click', () => dialog.showModal());
  dialog.querySelectorAll('[data-dashboard-messages-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });

  const recipientForm = dialog.querySelector('[data-message-recipient-form]');
  if (!recipientForm) return;
  const allUsers = recipientForm.querySelector('[data-message-all-users]');
  const recipients = [...recipientForm.querySelectorAll('[data-message-recipient]')];
  const audience = recipientForm.querySelector('[data-message-audience]');
  const summary = recipientForm.querySelector('[data-message-recipient-summary]');
  const syncRecipients = () => {
    const selected = recipients.filter(input => input.checked);
    if (allUsers.checked) recipients.forEach(input => { input.checked = false; });
    audience.value = allUsers.checked ? 'all' : 'users';
    summary.textContent = allUsers.checked ? 'All active users' : `${selected.length} user${selected.length === 1 ? '' : 's'} selected`;
  };
  allUsers.addEventListener('change', () => {
    if (!allUsers.checked && !recipients.some(option => option.checked)) allUsers.checked = true;
    syncRecipients();
  });
  recipients.forEach(input => input.addEventListener('change', () => {
    if (input.checked) allUsers.checked = false;
    if (!recipients.some(option => option.checked)) allUsers.checked = true;
    syncRecipients();
  }));
})();
