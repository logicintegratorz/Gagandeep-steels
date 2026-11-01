(() => {
  const storageKey = 'crm-theme';
  const valid = ['light', 'dark'];
  const saved = () => {
    try { const value = localStorage.getItem(storageKey); return valid.includes(value) ? value : 'light'; }
    catch { return 'light'; }
  };
  const apply = theme => {
    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.appearance = theme;
    document.querySelectorAll('[data-theme-option]').forEach(button => {
      const selected = button.dataset.themeOption === theme;
      button.setAttribute('aria-pressed', String(selected));
    });
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      const next = theme === 'dark' ? 'light' : 'dark';
      button.dataset.themeCurrent = theme;
      button.setAttribute('aria-label', `Use ${next} theme`);
      button.title = `Use ${next} theme`;
      button.querySelectorAll('[data-theme-icon]').forEach(icon => { icon.hidden = icon.dataset.themeIcon !== next; });
    });
  };
  apply(saved());
  document.querySelectorAll('[data-theme-option]').forEach(button => button.addEventListener('click', () => {
    const theme = button.dataset.themeOption;
    if (!valid.includes(theme)) return;
    try { localStorage.setItem(storageKey, theme); } catch {}
    apply(theme);
  }));
  document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
    const theme = button.dataset.themeCurrent === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem(storageKey, theme); } catch {}
    apply(theme);
  }));
})();
