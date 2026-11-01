(() => {
  'use strict';
  const menu = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.querySelector('.nav-backdrop');
  const mobileNavigation = window.matchMedia('(max-width: 850px)');
  const setNavigation = expanded => {
    menu?.setAttribute('aria-expanded', String(expanded));
    document.body.classList.toggle('nav-open', expanded);
    if (backdrop) backdrop.hidden = !expanded;
    if (sidebar) sidebar.inert = mobileNavigation.matches && !expanded;
  };
  const closeNavigation = () => { setNavigation(false); menu?.focus(); };
  document.querySelector('.sidebar-close')?.addEventListener('click', closeNavigation);
  backdrop?.addEventListener('click', closeNavigation);
  mobileNavigation.addEventListener('change', () => setNavigation(false));
  setNavigation(false);
  const accountControl = document.querySelector('[data-dashboard-account-control]');
  if (accountControl) {
    const accountTrigger = accountControl.querySelector('summary');
    document.addEventListener('click', event => {
      if (accountControl.open && !accountControl.contains(event.target)) accountControl.open = false;
    });
    document.addEventListener('keydown', event => {
      if (event.key !== 'Escape' || !accountControl.open) return;
      accountControl.open = false;
      accountTrigger?.focus();
    });
  }
  menu?.addEventListener('click', () => {
    const expanded = menu.getAttribute('aria-expanded') === 'true';
    setNavigation(!expanded);
    if (!expanded) sidebar?.querySelector('.sidebar-close')?.focus();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('nav-open')) closeNavigation();
    if (event.key === 'Tab' && mobileNavigation.matches && document.body.classList.contains('nav-open')) {
      const links = Array.from(sidebar.querySelectorAll('a, button')).filter(element => element.getClientRects().length);
      const first = links[0], last = links[links.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });
  const dialog = document.getElementById('delete-dialog');
  let pendingForm = null;
  document.querySelectorAll('[data-delete-trigger]').forEach(button => { button.disabled = false; });
  document.addEventListener('submit', event => {
      const form = event.target;
      if (!form.matches('[data-delete-form]')) return;
      event.preventDefault();
      pendingForm = form;
      document.getElementById('delete-description').textContent = form.dataset.description;
      dialog.showModal();
  });
  document.getElementById('cancel-delete')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('close', () => { pendingForm = null; });
  document.getElementById('confirm-delete')?.addEventListener('click', event => {
    if (!pendingForm) return;
    event.currentTarget.disabled = true;
    event.currentTarget.textContent = event.currentTarget.dataset.busyLabel || 'Deleting…';
    HTMLFormElement.prototype.submit.call(pendingForm);
  });
  document.querySelectorAll('[data-searchable-select]').forEach(wrapper => {
    const select = wrapper.querySelector('select');
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    const popup = document.createElement('div');
    popup.className = 'select-popup';
    popup.hidden = true;
    const search = document.createElement('input');
    search.type = 'search';
    search.placeholder = 'Search transporters…';
    search.autocomplete = 'off';
    search.setAttribute('aria-label', 'Search ' + select.getAttribute('aria-label').toLowerCase());
    search.setAttribute('role', 'combobox');
    search.setAttribute('aria-autocomplete', 'list');
    search.setAttribute('aria-expanded', 'false');
    const list = document.createElement('div');
    list.id = select.id + '-options';
    list.className = 'select-options';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', select.getAttribute('aria-label'));
    search.setAttribute('aria-controls', list.id);
    trigger.setAttribute('aria-controls', list.id);
    const status = document.createElement('p');
    status.className = 'select-status';
    status.setAttribute('role', 'status');
    popup.append(search, list, status);
    wrapper.append(trigger, popup);
    select.classList.add('sr-only');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    let active = -1;
    let buttons = [];
    const update = () => {
      const label = select.selectedOptions[0]?.textContent || 'Select transporter';
      trigger.textContent = label + ' ▾';
      trigger.setAttribute('aria-label', select.getAttribute('aria-label') + ': ' + label);
    };
    const close = (focus = false) => {
      popup.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      search.setAttribute('aria-expanded', 'false');
      search.removeAttribute('aria-activedescendant');
      if (focus) trigger.focus();
    };
    const choose = value => {
      select.value = value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      update();
      close(true);
    };
    const render = () => {
      list.replaceChildren();
      active = -1;
      search.removeAttribute('aria-activedescendant');
      const term = search.value.trim().toLocaleLowerCase();
      const options = Array.from(select.options).filter(option =>
        (!select.required || option.value !== '') && option.textContent.toLocaleLowerCase().includes(term));
      buttons = options.map((option, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.tabIndex = -1;
        button.id = list.id + '-' + index;
        button.textContent = option.textContent;
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', String(select.value === option.value));
        button.addEventListener('click', () => choose(option.value));
        list.append(button);
        return button;
      });
      status.textContent = buttons.length ? '' : 'No matching transporters. Add a name on the Transporters page.';
    };
    let loadVersion = 0;
    const open = async () => {
      popup.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      search.setAttribute('aria-expanded', 'true');
      search.value = '';
      render();
      search.focus();
      const version = ++loadVersion;
      try {
        const response = await fetch(wrapper.dataset.optionsUrl, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Could not load transporters');
        const names = await response.json();
        if (version !== loadVersion || !Array.isArray(names)) return;
        const value = select.value;
        const placeholder = select.options[0].textContent;
        select.replaceChildren(new Option(placeholder, ''));
        names.forEach(name => select.add(new Option(name, name)));
        select.value = value;
        if (select.selectedIndex < 0) select.selectedIndex = 0;
        update();
        if (!popup.hidden) render();
      } catch {
        if (!popup.hidden) status.textContent = 'Could not refresh names. Showing previously loaded transporters.';
        window.crmToast?.('Could not refresh transporter names. Showing previously loaded transporters. Try opening the list again.', 'warning');
      }
    };
    trigger.addEventListener('click', () => popup.hidden ? open() : close());
    trigger.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown') { event.preventDefault(); open(); }
    });
    search.addEventListener('input', render);
    search.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (!buttons.length) return;
        active = (active + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length;
        buttons.forEach((button, index) => button.classList.toggle('option-active', index === active));
        search.setAttribute('aria-activedescendant', buttons[active].id);
        buttons[active].scrollIntoView({ block: 'nearest' });
      }
      if (event.key === 'Enter') {
        event.preventDefault();
        if (buttons.length) buttons[active < 0 ? 0 : active].click();
      }
    });
    wrapper.addEventListener('keydown', event => {
      if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(true); }
    });
    wrapper.addEventListener('focusout', event => { if (!wrapper.contains(event.relatedTarget)) close(); });
    document.addEventListener('click', event => { if (!wrapper.contains(event.target)) close(); });
    select.addEventListener('invalid', event => { event.preventDefault(); open(); });
    select.addEventListener('change', update);
    select.labels.forEach(label => label.addEventListener('click', event => { event.preventDefault(); open(); }));
    update();
  });
  document.querySelectorAll('[data-save-form]').forEach(form => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('[data-submit]');
      button.disabled = true;
      button.querySelector('span').textContent = 'Saving…';
    });
  });
  window.addEventListener('pageshow', event => {
    const activeCall = document.querySelector('[data-lead-call-dialog][open], [data-lead-call-dialog][data-call-active="1"]');
    if (event.persisted && !activeCall) window.location.reload();
  });
})();

