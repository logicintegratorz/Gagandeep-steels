(() => {
  'use strict';
  const audience = document.querySelector('[data-audience]');
  if (!audience) return;
  const panels = document.querySelectorAll('[data-audience-panel]');
  const hint = document.querySelector('[data-audience-hint]');
  const update = () => {
    panels.forEach(panel => {
      const active = panel.dataset.audiencePanel === audience.value;
      panel.hidden = !active; panel.disabled = !active;
    });
    const count = document.querySelectorAll('[data-audience-panel="' + audience.value + '"] input:checked').length;
    hint.textContent = audience.value === 'all' ? 'This message will be sent to every active user.' : count + (audience.value === 'roles' ? ' roles' : ' users') + ' selected. Review your message before sending.';
  };
  audience.addEventListener('change',update);
  panels.forEach(panel => panel.addEventListener('change',update));
  document.querySelector('[data-recipient-search]')?.addEventListener('input',event => {
    const term = event.target.value.trim().toLocaleLowerCase();
    document.querySelectorAll('[data-recipient-option]').forEach(option => { option.hidden = !option.textContent.toLocaleLowerCase().includes(term); });
  });
  update();
})();
