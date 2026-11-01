(() => {
  const root = document.getElementById('dashboard-calendar');
  if (!root) return;
  const grid = root.querySelector('[data-calendar-grid]');
  const title = root.querySelector('[data-calendar-title]');
  const status = root.querySelector('[data-calendar-status]');
  const user = root.querySelector('[data-calendar-user]');
  const selectAll = root.querySelector('[data-calendar-all]');
  const kinds = [...root.querySelectorAll('[data-calendar-kind]')];
  const bulkKinds = kinds.filter(box => box.value !== 'Activity');
  const listDate = root.querySelector('[data-calendar-list-date]');
  const dateInput = root.querySelector('[data-calendar-date]');
  const dialog = document.getElementById('calendar-detail');
  let cursor = new Date();
  cursor.setHours(12, 0, 0, 0);
  let view = 'month';
  let events = [];
  const dayKey = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
  const move = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days, 12);
  const monday = date => move(date, -((date.getDay() + 6) % 7));
  const label = (date, options) => new Intl.DateTimeFormat('en-IN', options).format(date);
  const node = (tag, className, text) => { const element = document.createElement(tag); if (className) element.className = className; if (text !== undefined) element.textContent = text; return element; };
  const visibleEvents = () => events.filter(event => kinds.some(box => box.value === event.kind && box.checked));
  const syncSelectAll = () => {
    const selected = bulkKinds.filter(box => box.checked).length;
    selectAll.checked = selected === bulkKinds.length;
    selectAll.indeterminate = selected > 0 && selected < bulkKinds.length;
  };
  const range = () => {
    if (view === 'day') return [cursor, cursor];
    if (view === 'week') { const first = monday(cursor); return [first, move(first, 6)]; }
    if (view === 'list') return [move(cursor, -21), move(cursor, 20)];
    const first = monday(new Date(cursor.getFullYear(), cursor.getMonth(), 1, 12));
    return [first, move(first, 41)];
  };
  const openEvent = event => {
    dialog.querySelector('[data-calendar-detail-kind]').textContent = event.kind;
    dialog.querySelector('[data-calendar-detail-kind]').className = `calendar-pill calendar-${event.kind.toLowerCase().replace(/[^a-z]+/g, '-')}`;
    dialog.querySelector('[data-calendar-detail-title]').textContent = event.title;
    dialog.querySelector('[data-calendar-detail-date]').textContent = `${label(new Date(`${event.date}T12:00:00`), { dateStyle: 'full' })} · ${event.time} IST`;
    dialog.querySelector('[data-calendar-detail-lead]').textContent = event.lead;
    dialog.querySelector('[data-calendar-detail-person]').textContent = event.person;
    dialog.querySelector('[data-calendar-detail-status]').textContent = event.status || 'Recorded';
    dialog.querySelector('[data-calendar-detail-text]').textContent = event.detail;
    dialog.querySelector('[data-calendar-detail-link]').href = event.url;
    dialog.querySelector('[data-calendar-detail-link]').textContent = event.url.includes('/lead-work') ? 'Open scheduled work' : 'Open lead details';
    dialog.showModal();
  };
  const eventButton = event => {
    const button = node('button', `calendar-event calendar-${event.kind.toLowerCase().replace(/[^a-z]+/g, '-')}`, `${event.time} ${event.title}`);
    button.type = 'button'; button.title = `${event.kind}: ${event.title}`;
    button.addEventListener('click', () => openEvent(event));
    return button;
  };
  const render = () => {
    const [first, last] = range();
    const byDate = new Map();
    visibleEvents().forEach(event => { if (!byDate.has(event.date)) byDate.set(event.date, []); byDate.get(event.date).push(event); });
    grid.replaceChildren();
    listDate.hidden = view !== 'list';
    if (view === 'list') dateInput.value = dayKey(cursor);
    root.querySelectorAll('[data-calendar-view]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.calendarView === view)));
    title.textContent = view === 'month' ? label(cursor, { month: 'long', year: 'numeric' }) : view === 'day' ? label(cursor, { dateStyle: 'full' }) : `${label(first, { day: 'numeric', month: 'short' })} – ${label(last, { day: 'numeric', month: 'short', year: 'numeric' })}`;
    if (view === 'month' || view === 'week') {
      const calendar = node('div', 'calendar-grid');
      ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(day => calendar.append(node('div', 'calendar-weekday', day)));
      const length = view === 'month' ? 42 : 7;
      for (let index = 0; index < length; index++) {
        const date = move(first, index), key = dayKey(date), items = byDate.get(key) || [];
        const cell = node('div', 'calendar-cell' + (view === 'month' && date.getMonth() !== cursor.getMonth() ? ' is-outside' : '') + (key === dayKey(new Date()) ? ' is-today' : ''));
        const dateButton = node('button', 'calendar-date', String(date.getDate())); dateButton.type = 'button'; dateButton.setAttribute('aria-label', label(date, { dateStyle: 'full' }));
        dateButton.addEventListener('click', () => { cursor = date; view = 'day'; load(); }); cell.append(dateButton);
        items.slice(0, view === 'month' ? 3 : 10).forEach(event => cell.append(eventButton(event)));
        if (items.length > (view === 'month' ? 3 : 10)) { const more = node('button', 'calendar-more', `+${items.length - (view === 'month' ? 3 : 10)} more`); more.type = 'button'; more.addEventListener('click', () => { cursor = date; view = 'day'; load(); }); cell.append(more); }
        calendar.append(cell);
      }
      grid.append(calendar);
    } else {
      const list = node('div', 'calendar-agenda');
      let count = 0;
      for (let date = first; dayKey(date) <= dayKey(last); date = move(date, 1)) {
        const items = byDate.get(dayKey(date)) || [];
        const group = node('section', 'calendar-agenda-day'); group.dataset.date = dayKey(date); group.append(node('h3', '', label(date, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })));
        if (items.length) items.forEach(event => { group.append(eventButton(event)); count++; });
        else group.append(node('p', '', 'Nothing scheduled or recorded.'));
        list.append(group);
      }
      grid.append(list);
      const selectedDay = [...list.children].find(group => group.dataset.date === dayKey(cursor));
      if (view === 'list' && selectedDay) list.scrollTop = selectedDay.getBoundingClientRect().top - list.getBoundingClientRect().top;
    }
    status.textContent = `${visibleEvents().length} events in this period`;
  };
  const load = async () => {
    const [first, last] = range();
    const url = new URL(root.dataset.calendarUrl, window.location.href);
    url.searchParams.set('start', dayKey(first)); url.searchParams.set('end', dayKey(last));
    if (user.value) url.searchParams.set('user_id', user.value);
    status.textContent = 'Loading calendar…';
    try { const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }); if (!response.ok) throw new Error('Calendar unavailable'); events = (await response.json()).events || []; render(); }
    catch { grid.replaceChildren(); status.textContent = 'Calendar could not load. Please refresh and try again.'; }
  };
  root.querySelector('[data-calendar-prev]').addEventListener('click', () => { cursor = view === 'month' ? new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1, 12) : move(cursor, view === 'week' ? -7 : view === 'list' ? -42 : -1); load(); });
  root.querySelector('[data-calendar-next]').addEventListener('click', () => { cursor = view === 'month' ? new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1, 12) : move(cursor, view === 'week' ? 7 : view === 'list' ? 42 : 1); load(); });
  root.querySelector('[data-calendar-today]').addEventListener('click', () => { cursor = new Date(); cursor.setHours(12, 0, 0, 0); load(); });
  dateInput.addEventListener('change', () => {
    if (!dateInput.value) return;
    const selected = new Date(`${dateInput.value}T12:00:00`);
    if (Number.isNaN(selected.getTime())) return;
    cursor = selected;
    view = 'list';
    load();
  });
  root.querySelectorAll('[data-calendar-view]').forEach(button => button.addEventListener('click', () => { view = button.dataset.calendarView; if (view === 'list') { cursor = new Date(); cursor.setHours(12, 0, 0, 0); } load(); }));
  selectAll.addEventListener('change', () => {
    bulkKinds.forEach(box => { box.checked = selectAll.checked; });
    selectAll.indeterminate = false;
    render();
  });
  kinds.forEach(box => box.addEventListener('change', () => { syncSelectAll(); render(); }));
  syncSelectAll();
  user.addEventListener('change', load);
  dialog.querySelector('[data-calendar-close]').addEventListener('click', () => dialog.close());
  const addDialog = document.getElementById('calendar-add-dialog');
  if (addDialog) {
    root.querySelector('[data-calendar-add]').addEventListener('click', () => addDialog.showModal());
    addDialog.querySelector('[data-calendar-add-close]').addEventListener('click', () => addDialog.close());
    const search = addDialog.querySelector('[data-calendar-lead-search]');
    const results = addDialog.querySelector('[data-calendar-lead-results]');
    const form = addDialog.querySelector('[data-calendar-add-form]');
    const kindSelect = form.querySelector('[name="kind"]');
    const assignee = form.querySelector('[name="assigned_to"]');
    const updateAssignees = () => {
      Array.from(assignee.options).forEach(option => { option.disabled = !option.dataset.workKinds.split(',').includes(kindSelect.value); });
      if (assignee.selectedOptions[0]?.disabled) assignee.value = Array.from(assignee.options).find(option => !option.disabled)?.value || '';
    };
    kindSelect.addEventListener('change', updateAssignees);
    updateAssignees();
    let timer;
    search.addEventListener('input', () => {
      clearTimeout(timer);
      results.replaceChildren(new Option('Search and select a lead', ''));
      if (search.value.trim().length < 2) return;
      timer = setTimeout(async () => {
        const url = new URL(addDialog.dataset.leadSearchUrl, window.location.href);
        url.searchParams.set('q', search.value.trim());
        try {
          const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
          if (!response.ok) throw new Error('Search failed');
          const leads = await response.json();
          results.replaceChildren(new Option(leads.length ? 'Select a lead' : 'No matching leads', ''));
          leads.forEach(lead => results.add(new Option(`${lead.name}${lead.company ? ' · ' + lead.company : ''}`, lead.id)));
        } catch { results.replaceChildren(new Option('Search failed; try again', '')); }
      }, 250);
    });
    form.addEventListener('submit', event => {
      if (!results.value) { event.preventDefault(); results.setCustomValidity('Choose a lead first.'); results.reportValidity(); return; }
      results.setCustomValidity('');
      form.action = form.dataset.actionTemplate.replace('LEAD_ID', encodeURIComponent(results.value));
    });
    results.addEventListener('change', () => results.setCustomValidity(''));
  }
  load();
})();
