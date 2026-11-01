document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-auto-call-filters]');
  if (!form) return;

  form.querySelectorAll('select').forEach((field) => field.addEventListener('change', () => form.requestSubmit()));

  const from = form.elements.from;
  const to = form.elements.to;
  from?.addEventListener('change', () => {
    to.value = from.value;
    to.min = from.value;
    form.requestSubmit();
  });
  to?.addEventListener('change', () => form.requestSubmit());
});
