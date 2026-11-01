document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.querySelector('[data-chat-group-dialog]');
  const open = document.querySelector('[data-chat-group-open]');
  const close = [...document.querySelectorAll('[data-chat-group-close]')];
  open?.addEventListener('click', () => dialog?.showModal());
  close.forEach(button => button.addEventListener('click', () => dialog?.close()));
  dialog?.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  const messages = document.querySelector('.chat-messages');
  if (messages) messages.scrollTop = messages.scrollHeight;
});
