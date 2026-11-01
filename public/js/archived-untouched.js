(() => {
  const archive = document.querySelector('[data-archive-home]');
  if (!archive) return;
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') window.location.assign(archive.dataset.archiveHome);
  });
})();
