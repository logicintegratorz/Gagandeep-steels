document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const edge = document.querySelector('.extended-sidebar-edge');
  const sidebar = document.getElementById('sidebar');
  const desktop = matchMedia('(min-width: 851px)');
  if (!body.classList.contains('extended-leads') || !edge || !sidebar) return;
  const open = () => {
    if (!desktop.matches) return;
    sidebar.inert = false;
    body.classList.add('extended-sidebar-open');
    edge.setAttribute('aria-expanded', 'true');
  };
  const close = () => {
    body.classList.remove('extended-sidebar-open');
    edge.setAttribute('aria-expanded', 'false');
    if (desktop.matches) sidebar.inert = true;
  };
  close();
  edge.addEventListener('pointerenter', open);
  edge.addEventListener('click', open);
  sidebar.addEventListener('pointerleave', close);
  sidebar.addEventListener('focusin', open);
  sidebar.addEventListener('focusout', event => { if (!sidebar.contains(event.relatedTarget)) close(); });
  desktop.addEventListener('change', close);
  document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
});
