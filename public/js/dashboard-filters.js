(() => {
  const form = document.querySelector('[data-auto-contact-filters]');
  const section = form?.closest('.dash-contacts');
  if (!section) return;

  const topStack = document.querySelector('.dash-top-stack');
  const syncContactsStickyOffset = () => document.documentElement.style.setProperty('--dash-top-stack-height', `${Math.ceil(topStack?.getBoundingClientRect().height || 0)}px`);
  syncContactsStickyOffset();
  window.addEventListener('resize', syncContactsStickyOffset);
  if (window.ResizeObserver && topStack) new ResizeObserver(syncContactsStickyOffset).observe(topStack);

  const inputs = [...form.querySelectorAll('input[name]')];
  const status = section.querySelector('[data-contact-filter-status]');
  const clear = section.querySelector('.dash-clear-filters');
  const exportLink = section.querySelector('[data-contacts-export-url]');
  const expandButton = section.querySelector('[data-contacts-expand]');
  const floatActions = document.createElement('div');
  floatActions.className = 'dash-contact-float-actions';
  floatActions.hidden = true;
  floatActions.innerHTML = '<button type="button" class="dash-contact-float-button" data-contacts-page-up aria-label="Scroll up 10 contacts" title="Scroll up 10 contacts">↑</button><button type="button" class="dash-contact-float-button" data-contacts-page-down aria-label="Scroll down 10 contacts" title="Scroll down 10 contacts">↓</button>';
  document.body.append(floatActions);
  let timer;
  let pending;
  let requestId = 0;
  const multiButton = section.querySelector('[data-contact-multi-open]');
  const multiDialog = document.querySelector('[data-contact-multi-dialog]');
  const archiveFilterButton = section.querySelector('[data-contact-archive-filter]');
  let archiveUntouchedMode = new URLSearchParams(location.search).get('contacts_filter') === 'touched';
  let contactStatusFilter = archiveUntouchedMode ? 'touched' : 'all';
  const applyStatusFilter = () => {
    section.querySelectorAll('.dash-contact-row').forEach(row => {
      row.hidden = (contactStatusFilter === 'touched' && row.dataset.contactTouched !== 'true') || (contactStatusFilter === 'untouched' && row.dataset.contactUntouched !== 'true');
    });
    const contactsHeading = section.querySelector('[data-contacts-heading]');
    if (contactsHeading) contactsHeading.textContent = archiveUntouchedMode ? 'Touched contacts' : 'Contacts';
    if (archiveFilterButton) {
      archiveFilterButton.classList.toggle('is-active', archiveUntouchedMode);
      archiveFilterButton.setAttribute('aria-pressed', String(archiveUntouchedMode));
      archiveFilterButton.title = archiveUntouchedMode ? 'Show all contacts' : 'Hide untouched leads and show touched leads';
      const label = archiveFilterButton.querySelector('[data-contact-archive-label]');
      if (label) label.textContent = archiveUntouchedMode ? 'Unarchive opened leads' : 'Archive untouched leads';
    }
  };
  const currentDashboardUrl = () => new URL(location.href);
  const writeArchiveFilterUrl = pushed => {
    const url = currentDashboardUrl();
    if (archiveUntouchedMode) url.searchParams.set('contacts_filter', 'touched');
    else { url.searchParams.delete('contacts_filter'); url.searchParams.delete('contacts'); }
    if (pushed) history.pushState({}, '', url);
    else history.replaceState(history.state, '', url);
  };
  archiveFilterButton?.addEventListener('click', () => {
    archiveUntouchedMode = !archiveUntouchedMode;
    contactStatusFilter = archiveUntouchedMode ? 'touched' : 'all';
    writeArchiveFilterUrl(true);
    applyStatusFilter();
  });
  const selectedCount = () => section.querySelectorAll('[data-contact-select]:checked').length;
  const syncSelection = () => {
    const selected = selectedCount();
    const selectable = [...section.querySelectorAll('[data-contact-select]')];
    const selectAll = section.querySelector('[data-contact-select-all]');
    section.querySelectorAll('[data-contact-selected-count]').forEach(item => { item.textContent = selected; });
    if (multiDialog) multiDialog.querySelectorAll('[data-contact-multi-count]').forEach(item => { item.textContent = selected; });
    if (multiButton) {
      multiButton.disabled = selected === 0;
      multiButton.setAttribute('aria-label', selected ? `Open multi actions for ${selected} selected lead${selected === 1 ? '' : 's'}` : 'Select one or more leads to use multi actions');
    }
    if (selectAll) {
      selectAll.checked = selectable.length > 0 && selected === selectable.length;
      selectAll.indeterminate = selected > 0 && selected < selectable.length;
    }
  };
  multiButton?.addEventListener('click', () => {
    if (selectedCount() && multiDialog?.showModal) multiDialog.showModal();
  });
  multiDialog?.querySelectorAll('[data-contact-multi-close]').forEach(button => button.addEventListener('click', () => multiDialog.close()));
  multiDialog?.addEventListener('click', event => {
    if (event.target === multiDialog) multiDialog.close();
  });
  const multiMenu = multiDialog?.querySelector('[data-multi-menu]');
  const multiForm = multiDialog?.querySelector('[data-multi-form]');
  const multiStatus = multiDialog?.querySelector('[data-multi-status]');
  const multiLinks = multiDialog?.querySelector('[data-multi-links]');
  const resetMulti = () => {
    if (!multiForm) return;
    multiForm.reset(); multiForm.hidden = true; multiMenu.hidden = false;
    multiForm.querySelectorAll('[data-fields]').forEach(field => field.classList.remove('is-active'));
    multiStatus.textContent = ''; multiStatus.classList.remove('is-error');
    multiLinks.hidden = true; multiLinks.replaceChildren();
  };
  multiDialog?.addEventListener('close', resetMulti);
  multiDialog?.querySelector('[data-multi-back]')?.addEventListener('click', resetMulti);
  multiDialog?.querySelectorAll('[data-contact-multi-option]').forEach(button => button.addEventListener('click', () => {
    const action = button.dataset.contactMultiOption;
    multiMenu.hidden = true; multiForm.hidden = false; multiForm.elements.action.value = action;
    multiForm.querySelector('[data-multi-form-label]').textContent = button.dataset.multiLabel;
    multiForm.querySelectorAll('[data-fields]').forEach(field => field.classList.toggle('is-active', field.dataset.fields.split(' ').includes(action)));
    if (['reminder', 'feedback'].includes(action) && !multiForm.elements.due_at.value) {
      const due = new Date(Date.now() + 86400000); due.setMinutes(due.getMinutes() - due.getTimezoneOffset());
      multiForm.elements.due_at.value = due.toISOString().slice(0, 16);
    }
    multiForm.querySelector('.is-active input, .is-active textarea, .is-active select')?.focus();
  }));
  multiForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const action = multiForm.elements.action.value;
    const ids = [...section.querySelectorAll('[data-contact-select]:checked')].map(box => box.value);
    if (!action || !ids.length) { multiStatus.textContent = 'Select at least one lead.'; multiStatus.classList.add('is-error'); return; }
    const value = name => multiForm.elements[name]?.value?.trim() || '';
    const body = new FormData(); body.append('_token', multiForm.elements._token.value); body.append('action', action); ids.forEach(id => body.append('lead_ids[]', id));
    const values = {
      call: { details: value('call_details') }, email: { subject: value('email_subject'), message: value('email_message') },
      whatsapp: { message: value('message') }, sms: { message: value('message') },
      reminder: { title: value('work_title'), due_at: value('due_at'), details: value('work_details') },
      feedback: { title: value('work_title'), due_at: value('due_at'), details: value('work_details') },
      note: { title: value('note_title'), details: value('note_details') },
      opportunity: { stage: value('opportunity_stage'), value: value('value'), details: value('opportunity_details') },
      list: { group_id: value('group_id') }, user: { owner_id: value('owner_id') }, stage: { stage: value('stage') },
      category: { category: value('category') }, delete: { confirmation: value('confirmation') }
    };
    Object.entries(values[action] || {}).forEach(([key, item]) => { if (item !== '') body.append(key, item); });
    const submit = multiForm.querySelector('[data-multi-submit]'); submit.disabled = true; multiStatus.textContent = 'Applying action…'; multiStatus.classList.remove('is-error'); multiLinks.hidden = true;
    try {
      const response = await fetch(multiForm.getAttribute('action'), { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' } });
      const result = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Action could not be completed.');
      multiStatus.textContent = result.message;
      if (result.links?.length) {
        multiLinks.replaceChildren(...result.links.map(item => { const link = document.createElement('a'); link.href = item.url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.innerHTML = `<span>${item.name}</span><strong>Open ${item.channel} ↗</strong>`; return link; }));
        multiLinks.hidden = false;
      }
      if (result.refresh) {
        const url = new URL(location.href); if (action !== 'delete') url.searchParams.set('contacts', ids[0]); else url.searchParams.delete('contacts'); url.hash = 'contacts-table'; location.assign(url);
      }
    } catch (error) { multiStatus.textContent = error.message; multiStatus.classList.add('is-error'); }
    finally { submit.disabled = false; }
  });

  const sizeContacts = () => {
    const scroll = section.querySelector('.dash-table-scroll');
    const rows = [...scroll.querySelectorAll('tbody .dash-contact-row')];
    scroll.scrollTop = 0;
    const lastVisible = rows[Math.min(9, rows.length - 1)];
    if (lastVisible) {
      const height = Math.ceil(lastVisible.getBoundingClientRect().bottom - scroll.getBoundingClientRect().top) + 1;
      scroll.style.setProperty('--contacts-collapsed-height', `${height}px`);
    } else {
      scroll.style.removeProperty('--contacts-collapsed-height');
    }
  };
  const setExpanded = expanded => {
    section.dataset.contactsExpanded = String(expanded);
    expandButton.setAttribute('aria-expanded', String(expanded));
    expandButton.setAttribute('aria-label', expanded ? 'Collapse contacts list' : 'Expand contacts list');
    expandButton.querySelector('[data-contacts-expand-label]').textContent = expanded ? 'Show 10' : 'Expand list';
    floatActions.hidden = !expanded;
    if (!expanded) section.querySelector('.dash-table-scroll')?.scrollTo({ top: 0, behavior: 'auto' });
  };
  expandButton.addEventListener('click', event => {
    event.preventDefault();
    setExpanded(section.dataset.contactsExpanded !== 'true');
  });

  const visibleRowIndex = rows => {
    const viewportTop = 8;
    const index = rows.findIndex(row => row.getBoundingClientRect().bottom > viewportTop);
    return index < 0 ? rows.length - 1 : index;
  };
  const scrollRows = direction => {
    const rows = [...section.querySelectorAll('tbody .dash-contact-row')];
    if (!rows.length) return;
    const current = visibleRowIndex(rows);
    const target = Math.max(0, Math.min(rows.length - 1, current + direction * 10));
    rows[target].scrollIntoView({ behavior: 'smooth', block: 'start' });
  };
  const upButton = floatActions.querySelector('[data-contacts-page-up]');
  let upClickTimer;
  let lastUpTapAt = 0;
  const handleUpAction = event => {
    event.preventDefault();
    const now = Date.now();
    if (now - lastUpTapAt < 350) {
      clearTimeout(upClickTimer);
      lastUpTapAt = 0;
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }
    lastUpTapAt = now;
    clearTimeout(upClickTimer);
    upClickTimer = setTimeout(() => {
      lastUpTapAt = 0;
      scrollRows(-1);
    }, 350);
  };
  upButton.addEventListener('click', handleUpAction);
  upButton.addEventListener('dblclick', event => event.preventDefault());
  floatActions.querySelector('[data-contacts-page-down]').addEventListener('click', () => scrollRows(1));

  const hasFilters = () => inputs.some(input => input.value.trim() !== '');
  const syncClear = () => { clear.hidden = !hasFilters(); };
  const filterUrl = () => {
    const url = new URL(form.action, location.href);
    url.search = '';
    inputs.forEach(input => {
      if (input.value.trim() !== '') url.searchParams.set(input.name, input.value.trim());
    });
    return url;
  };
  const syncExport = () => {
    if (!exportLink) return;
    const url = new URL(exportLink.dataset.contactsExportUrl, location.href);
    inputs.forEach(input => {
      if (input.value.trim() !== '') url.searchParams.set(input.name, input.value.trim());
    });
    exportLink.href = url.toString();
  };

  const load = async (url, updateHistory = true) => {
    pending?.abort();
    pending = new AbortController();
    const id = ++requestId;
    const scrollY = window.scrollY;
    status.textContent = 'Filtering contacts…';
    try {
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
        signal: pending.signal,
      });
      if (!response.ok) throw new Error('Could not filter contacts. Please try again.');
      const documentResult = new DOMParser().parseFromString(await response.text(), 'text/html');
      const next = documentResult.querySelector('.dash-contacts');
      if (!next || id !== requestId) throw new Error('Contact results are unavailable.');
      section.querySelector('.dash-table-scroll').replaceWith(next.querySelector('.dash-table-scroll'));
      section.querySelector('.dash-table-bottom').replaceWith(next.querySelector('.dash-table-bottom'));
      section.querySelector('.dash-contacts-title .dash-count').textContent = next.querySelector('.dash-contacts-title .dash-count').textContent;
      sizeContacts();
      applyStatusFilter();
      syncSelection();
      if (updateHistory) history.replaceState(history.state, '', url);
      status.textContent = 'Contacts updated.';
      window.scrollTo({ top: scrollY, behavior: 'auto' });
    } catch (error) {
      if (error.name !== 'AbortError' && id === requestId) status.textContent = error.message;
    }
  };

  const schedule = () => {
    clearTimeout(timer);
    pending?.abort();
    requestId++;
    syncClear();
    syncExport();
    timer = setTimeout(() => load(filterUrl()), 500);
  };
  inputs.forEach(input => {
    input.addEventListener('input', schedule);
    input.addEventListener('change', schedule);
  });
  form.addEventListener('submit', event => {
    event.preventDefault();
    clearTimeout(timer);
    load(filterUrl());
  });
  clear.addEventListener('click', event => {
    event.preventDefault();
    clearTimeout(timer);
    inputs.forEach(input => { input.value = ''; });
    syncClear();
    syncExport();
    load(filterUrl());
  });
  section.addEventListener('change', event => {
    if (event.target.matches('[data-contact-select-all]')) {
      section.querySelectorAll('[data-contact-select]').forEach(box => { box.checked = event.target.checked; });
      syncSelection();
      return;
    }
    if (event.target.matches('[data-contact-select]')) syncSelection();
  });
  section.addEventListener('click', event => {
    const leadLink = event.target.closest('[data-contact-lead-link]');
    if (leadLink && archiveUntouchedMode) {
      const leadUrl = new URL(leadLink.href, location.href);
      leadUrl.searchParams.set('return_filter', 'archive');
      leadLink.href = leadUrl.toString();
    }
    if (event.target.closest('[data-contacts-expand]')) return;
    const link = event.target.closest('.dash-contact-pages a');
    if (link) {
      event.preventDefault();
      load(new URL(link.href));
      return;
    }
    if (!event.target.closest('a, button, input, select, textarea, label, summary, form, [role="button"]')) setExpanded(true);
  });
  document.addEventListener('click', event => {
    if (section.dataset.contactsExpanded !== 'true') return;
    if (section.contains(event.target) || floatActions.contains(event.target) || multiDialog?.open) return;
    setExpanded(false);
  });
  window.addEventListener('resize', sizeContacts);
  window.addEventListener('popstate', () => {
    archiveUntouchedMode = new URLSearchParams(location.search).get('contacts_filter') === 'touched';
    contactStatusFilter = archiveUntouchedMode ? 'touched' : 'all';
    applyStatusFilter();
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape' || !archiveUntouchedMode) return;
    event.preventDefault();
    archiveUntouchedMode = false;
    contactStatusFilter = 'all';
    writeArchiveFilterUrl(true);
    applyStatusFilter();
  });
  requestAnimationFrame(sizeContacts);
  setExpanded(section.dataset.contactsExpanded === 'true');
  syncClear();
  syncExport();
  syncSelection();
  applyStatusFilter();
  const highlightReturnedContact = () => {
    const returnedContactId = new URLSearchParams(location.search).get('contacts');
    if (!returnedContactId) return;
    setExpanded(true);
    requestAnimationFrame(() => {
      const row = section.querySelector(`[data-contact-id="${CSS.escape(returnedContactId)}"]`);
      if (!row) return;
      row.classList.remove('dash-contact-row--returned');
      row.classList.add('dash-contact-row--returned');
      row.querySelector('[data-contact-select]')?.focus({ preventScroll: true });
      row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      window.setTimeout(() => row.classList.remove('dash-contact-row--returned'), 2000);
    });
  };
  highlightReturnedContact();
  window.addEventListener('pageshow', event => {
    if (event.persisted) highlightReturnedContact();
  });
})();
