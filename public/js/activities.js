(() => {
  const root = document.querySelector('[data-activities]'); if (!root) return;
  const editor = document.querySelector('[data-activity-dialog]');
  const completion = document.querySelector('[data-completion-dialog]');
  const form = editor?.querySelector('[data-activity-form]');
  const picker = editor?.querySelector('[data-contact-picker]');
  const rich = editor?.querySelector('[data-rich-editor]');
  const richContent = rich?.querySelector('[data-rich-content]');
  const richInput = editor?.querySelector('[data-rich-input]');
  let savedRange = null;
  const rememberRange = () => {
    const selection = window.getSelection();
    if (!selection?.rangeCount || !richContent?.contains(selection.anchorNode)) return;
    savedRange = selection.getRangeAt(0).cloneRange();
  };
  const restoreRange = () => {
    richContent?.focus();
    if (!savedRange) return;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(savedRange);
  };
  const syncRich = () => { if (richInput && richContent) richInput.value = richContent.innerHTML.trim(); };
  const runCommand = (command, value = null) => { restoreRange(); document.execCommand(command, false, value); rememberRange(); syncRich(); };
  const setContacts = (ids = []) => {
    if (!picker) return;
    const selected = ids.map(String);
    picker.querySelectorAll('input[type="checkbox"]').forEach(input => { input.checked = selected.includes(input.value); });
    const selectedContacts = [...picker.querySelectorAll('input:checked')].map(input => ({ id: input.value, name: input.closest('[data-contact-option]').querySelector('span').textContent }));
    const names = selectedContacts.map(contact => contact.name);
    const summary = picker.querySelector('[data-contact-summary]');
    if (summary) {
      summary.replaceChildren();
      selectedContacts.forEach(contact => {
        const chip = document.createElement('span'); chip.className = 'contact-chip';
        const name = document.createElement('span'); name.textContent = contact.name;
        const remove = document.createElement('button'); remove.type = 'button'; remove.dataset.removeContact = contact.id; remove.setAttribute('aria-label', `Remove ${contact.name}`); remove.textContent = '×';
        chip.append(name, remove); summary.append(chip);
      });
      summary.hidden = names.length === 0;
    }
    const clientLabel = editor?.querySelector('[data-calendar-client]');
    const clientCheck = editor?.querySelector('[data-calendar-client-check]');
    if (clientLabel) clientLabel.textContent = names.length ? names.join(', ') : 'Not linked';
    if (clientCheck) { clientCheck.disabled = names.length === 0; if (!names.length) clientCheck.checked = false; }
  };
  const updateAssigneeLabel = () => {
    const assignee = form?.elements.assigned_to;
    const label = editor?.querySelector('[data-calendar-assignee]');
    if (assignee && label) label.textContent = assignee.options[assignee.selectedIndex]?.text || 'Unassigned';
  };
  const closePicker = () => { if (!picker) return; picker.querySelector('[data-contact-menu]').hidden = true; picker.querySelector('[data-contact-toggle]').setAttribute('aria-expanded', 'false'); const search = picker.querySelector('[data-contact-search]'); if (search) search.value = ''; picker.querySelectorAll('[data-contact-option]').forEach(option => { option.hidden = false; option.style.display = 'grid'; option.setAttribute('aria-hidden', 'false'); }); picker.querySelector('[data-contact-empty]').hidden = true; };
  const closeEditor = () => editor?.close();
  const openNew = () => { form.reset(); form.elements.assigned_to.value = ''; richContent.innerHTML = ''; syncRich(); setContacts(); updateAssigneeLabel(); closePicker(); form.action = form.dataset.storeUrl || form.action; form.querySelector('[data-method]').value = 'POST'; editor.querySelector('[data-dialog-title]').textContent = 'Add new activity'; form.querySelector('[data-activity-submit]').textContent = 'Create'; editor.showModal(); };
  form.dataset.storeUrl = form.action;
  document.querySelector('[data-new-activity]')?.addEventListener('click', openNew);
  document.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', closeEditor));
  form?.elements.assigned_to?.addEventListener('change', updateAssigneeLabel);
  document.querySelectorAll('[data-edit-activity]').forEach(button => button.addEventListener('click', () => {
    form.reset(); form.action = `/activities/${button.dataset.id}`; form.querySelector('[data-method]').value = 'PUT';
    form.elements.name.value = button.dataset.name; form.elements.activity_type.value = button.dataset.type; form.elements.scheduled_at.value = button.dataset.scheduled; form.elements.assigned_to.value = button.dataset.assigned || ''; setContacts((button.dataset.leads || '').split(',').filter(Boolean)); updateAssigneeLabel(); richContent.innerHTML = button.dataset.description || ''; syncRich(); form.elements.link.value = button.dataset.link || ''; form.elements.send_calendar_to_assignee.checked = button.dataset.calendarAssignee === '1'; form.elements.send_calendar_to_client.checked = button.dataset.calendarClient === '1';
    editor.querySelector('[data-dialog-title]').textContent = 'Edit activity'; form.querySelector('[data-activity-submit]').textContent = 'Update'; editor.showModal();
  }));
  document.querySelectorAll('[data-status-select]').forEach(select => select.addEventListener('change', () => { if (!select.value) return; const statusForm = select.closest('form'); if (select.value === 'Complete') { completion.querySelector('[data-completion-form]').action = statusForm.dataset.statusUrl; completion.showModal(); select.value = ''; return; } const input = document.createElement('input'); input.type = 'hidden'; input.name = 'status'; input.value = select.value; statusForm.append(input); statusForm.submit(); }));
  document.querySelectorAll('[data-completion-close]').forEach(button => button.addEventListener('click', () => completion?.close()));
  document.querySelectorAll('[data-delete-activity]').forEach(form => form.addEventListener('submit', event => { if (!confirm('Delete this activity?')) event.preventDefault(); }));
  picker?.querySelector('[data-contact-toggle]').addEventListener('click', () => { const menu = picker.querySelector('[data-contact-menu]'); menu.hidden = !menu.hidden; picker.querySelector('[data-contact-toggle]').setAttribute('aria-expanded', String(!menu.hidden)); if (!menu.hidden) picker.querySelector('[data-contact-search]').focus(); });
  picker?.querySelectorAll('input[type="checkbox"]').forEach(input => input.addEventListener('change', () => setContacts([...picker.querySelectorAll('input:checked')].map(item => item.value))));
  picker?.addEventListener('click', event => {
    const remove = event.target.closest('[data-remove-contact]');
    if (!remove) return;
    event.preventDefault(); event.stopPropagation();
    const checkbox = picker.querySelector(`input[type="checkbox"][value="${CSS.escape(remove.dataset.removeContact)}"]`);
    if (checkbox) checkbox.checked = false;
    setContacts([...picker.querySelectorAll('input:checked')].map(item => item.value));
  });
  const filterContacts = event => {
    if (!picker) return;
    const query = event.target.value.toLowerCase().trim();
    let visible = 0;
    picker.querySelectorAll('[data-contact-option]').forEach(option => {
      const searchableText = (option.dataset.searchText || option.textContent || '').toLowerCase();
      const match = !query || searchableText.includes(query);
      option.hidden = !match;
      option.style.display = match ? 'grid' : 'none';
      option.setAttribute('aria-hidden', String(!match));
      if (match) visible++;
    });
    picker.querySelector('[data-contact-empty]').hidden = visible !== 0;
  };
  const contactSearch = picker?.querySelector('[data-contact-search]');
  ['input', 'keyup', 'search', 'change'].forEach(eventName => contactSearch?.addEventListener(eventName, filterContacts));
  contactSearch?.addEventListener('focus', () => { const menu = picker.querySelector('[data-contact-menu]'); menu.hidden = false; picker.querySelector('[data-contact-toggle]').setAttribute('aria-expanded', 'true'); });
  document.addEventListener('click', event => { if (picker && !picker.contains(event.target)) closePicker(); });
  rich?.querySelectorAll('[data-command]').forEach(button => {
    button.addEventListener('mousedown', event => event.preventDefault());
    button.addEventListener('click', () => runCommand(button.dataset.command, button.dataset.value || null));
  });
  rich?.querySelector('[data-rich-format]').addEventListener('change', event => runCommand('formatBlock', event.target.value));
  rich?.querySelector('[data-rich-colour]').addEventListener('input', event => runCommand('foreColor', event.target.value));
  rich?.querySelector('[data-rich-link]').addEventListener('click', () => { const url = prompt('Enter link URL'); if (url) runCommand('createLink', url); });
  rich?.querySelector('[data-rich-image]').addEventListener('click', () => { const url = prompt('Enter image URL'); if (url) runCommand('insertImage', url); });
  rich?.querySelector('[data-rich-emoji]').addEventListener('click', () => runCommand('insertText', prompt('Enter emoji', '🙂') || ''));
  richContent?.addEventListener('input', () => { rememberRange(); syncRich(); });
  ['keyup', 'mouseup', 'focus', 'select'].forEach(eventName => richContent?.addEventListener(eventName, rememberRange));
  form?.addEventListener('submit', syncRich);
  if (root.dataset.openNew === '1') openNew();
})();
