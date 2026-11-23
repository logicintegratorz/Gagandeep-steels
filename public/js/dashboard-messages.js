(() => {
  const dialog = document.querySelector('[data-dashboard-messages]');
  const opener = document.querySelector('[data-dashboard-messages-open]');
  if (!dialog || !opener) return;

  const openDialog = () => {
    // Each fresh open starts at the intended compact default size; the user
    // can still freely move and resize it while it is open.
    dialog.style.width = '';
    dialog.style.height = '';
    dialog.style.left = '';
    dialog.style.top = '';
    dialog.style.inset = '';
    dialog.style.margin = '';
    dialog.showModal();
    // Native dialogs are centered with auto margins. Convert that placement to
    // fixed pixel coordinates before resizing so a width change cannot shift it.
    requestAnimationFrame(() => {
      const bounds = dialog.getBoundingClientRect();
      dialog.style.inset = 'auto';
      dialog.style.margin = '0';
      dialog.style.left = `${bounds.left}px`;
      dialog.style.top = `${bounds.top}px`;
    });
  };
  opener.addEventListener('click', openDialog);
  if (dialog.dataset.openOnValidationError === 'true') openDialog();
  const dialogHeader = dialog.querySelector('.dashboard-messages-card > header');
  dialogHeader?.addEventListener('pointerdown', event => {
    if (event.target.closest('button, input, textarea, select, a, summary')) return;
    event.preventDefault();
    const bounds = dialog.getBoundingClientRect();
    const offsetX = event.clientX - bounds.left, offsetY = event.clientY - bounds.top;
    dialog.style.inset = 'auto';
    dialog.style.margin = '0';
    dialog.style.left = `${bounds.left}px`;
    dialog.style.top = `${bounds.top}px`;
    dialogHeader.setPointerCapture(event.pointerId);
    const move = pointer => {
      const edge = 12;
      const left = Math.max(edge, Math.min(pointer.clientX - offsetX, window.innerWidth - bounds.width - edge));
      const top = Math.max(edge, Math.min(pointer.clientY - offsetY, window.innerHeight - bounds.height - edge));
      dialog.style.left = `${left}px`;
      dialog.style.top = `${top}px`;
    };
    const end = () => {
      dialogHeader.removeEventListener('pointermove', move);
      dialogHeader.removeEventListener('pointerup', end);
      dialogHeader.removeEventListener('pointercancel', end);
    };
    dialogHeader.addEventListener('pointermove', move);
    dialogHeader.addEventListener('pointerup', end);
    dialogHeader.addEventListener('pointercancel', end);
  });
  const resizePopup = handle => {
    handle.addEventListener('pointerdown', event => {
      event.preventDefault();
      const direction = handle.dataset.messageResize;
      const bounds = dialog.getBoundingClientRect();
      const start = { x: event.clientX, y: event.clientY, left: bounds.left, top: bounds.top, width: bounds.width, height: bounds.height };
      dialog.style.inset = 'auto';
      dialog.style.margin = '0';
      dialog.style.left = `${start.left}px`;
      dialog.style.top = `${start.top}px`;
      handle.setPointerCapture(event.pointerId);
      const move = pointer => {
        const dx = pointer.clientX - start.x, dy = pointer.clientY - start.y;
        const minWidth = 320, minHeight = 240, edge = 12;
        let left = start.left, top = start.top, width = start.width, height = start.height;
        if (direction.includes('e')) width = start.width + dx;
        if (direction.includes('s')) height = start.height + dy;
        if (direction.includes('w')) { width = start.width - dx; left = start.left + dx; }
        if (direction.includes('n')) { height = start.height - dy; top = start.top + dy; }
        width = Math.max(minWidth, Math.min(width, window.innerWidth - edge * 2));
        height = Math.max(minHeight, Math.min(height, window.innerHeight - edge * 2));
        left = Math.max(edge, Math.min(left, window.innerWidth - width - edge));
        top = Math.max(edge, Math.min(top, window.innerHeight - height - edge));
        // Page-specific styles can set a different default dialog size.  Keep
        // an actively user-resized size authoritative until this dialog closes.
        dialog.style.setProperty('width', `${width}px`, 'important');
        dialog.style.setProperty('height', `${height}px`, 'important');
        dialog.style.left = `${left}px`; dialog.style.top = `${top}px`;
      };
      const end = () => { handle.removeEventListener('pointermove', move); handle.removeEventListener('pointerup', end); };
      handle.addEventListener('pointermove', move); handle.addEventListener('pointerup', end);
    });
  };
  dialog.querySelectorAll('[data-message-resize]').forEach(resizePopup);
  dialog.querySelectorAll('[data-dashboard-messages-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', event => {
    dialog.querySelectorAll('details[open]').forEach(panel => {
      if (!panel.contains(event.target)) panel.removeAttribute('open');
    });
  });

  const composer = dialog.querySelector('[data-message-recipient-form]');
  composer?.addEventListener('submit', () => {
    let marker = composer.querySelector('input[name="message_popup"]');
    if (!marker) {
      marker = document.createElement('input');
      marker.type = 'hidden';
      marker.name = 'message_popup';
      composer.append(marker);
    }
    marker.value = '1';
  });
  const composerMessage = composer?.querySelector('textarea[name="message"]');
  dialog.querySelectorAll('textarea[name="message"]').forEach(textarea => { textarea.required = false; });
  if (composerMessage) {
    const label = composerMessage.closest('label');
    if (label?.firstChild) label.firstChild.nodeValue = 'Message';
  }

  // Editing a sent message reuses the main composer. This keeps its title and
  // message visible at the top instead of opening a second cramped edit form.
  dialog.querySelectorAll('.dashboard-sent-messages details > summary').forEach(summary => {
    summary.addEventListener('click', event => {
      event.preventDefault();
      const editForm = summary.parentElement?.querySelector('form');
      if (!composer || !editForm) return;
      const titleInput = composer.querySelector('input[name="title"]');
      const editTitle = editForm.querySelector('input[name="title"]');
      const editMessage = editForm.querySelector('textarea[name="message"]');
      if (!titleInput || !composerMessage || !editTitle || !editMessage) return;

      titleInput.value = editTitle.value;
      composerMessage.value = editMessage.value;
      composer.action = editForm.action;
      let method = composer.querySelector('input[data-message-edit-method]');
      if (!method) {
        method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.dataset.messageEditMethod = 'true';
        composer.append(method);
      }
      method.value = 'PUT';
      const submit = composer.querySelector('button[type="submit"], button:not([type])');
      if (submit) submit.textContent = 'Update';
      composer.querySelector('.dashboard-recipient-picker')?.setAttribute('hidden', '');
      titleInput.focus();
    });
  });

  const messageTable = dialog.querySelector('.dashboard-message-table');
  if (messageTable) {
    const deletableRows = [...messageTable.tBodies[0].rows].filter(row => row.querySelectorAll('.dashboard-message-controls > form').length > 1);
    if (deletableRows.length) {
      const heading = messageTable.tHead.rows[0];
      const selectHeading = document.createElement('th');
      selectHeading.className = 'dashboard-message-select-column';
      selectHeading.innerHTML = '<label class="sr-only" for="dashboard-message-select-all">Select all messages</label><input id="dashboard-message-select-all" type="checkbox" aria-label="Select all messages">';
      heading.insertBefore(selectHeading, heading.firstChild);

      const selectedRows = deletableRows.map(row => {
        const checkboxCell = document.createElement('td');
        checkboxCell.className = 'dashboard-message-select-column';
        checkboxCell.innerHTML = '<input type="checkbox" data-dashboard-message-select aria-label="Select this message">';
        row.insertBefore(checkboxCell, row.firstChild);
        return { row, checkbox: checkboxCell.querySelector('input'), deleteForm: row.querySelector('.dashboard-message-controls > form:not(:last-child)') };
      });
      const selectAll = messageTable.querySelector('#dashboard-message-select-all');
      const bulkDelete = document.createElement('button');
      bulkDelete.type = 'button';
      bulkDelete.className = 'button secondary dashboard-message-bulk-delete';
      bulkDelete.hidden = true;
      dialog.querySelector('.dashboard-messages-card > header').insertBefore(bulkDelete, dialog.querySelector('[data-dashboard-messages-close]'));
      const syncBulkDelete = () => {
        const count = selectedRows.filter(item => item.checkbox.checked).length;
        bulkDelete.hidden = count === 0;
        bulkDelete.textContent = `Delete selected (${count})`;
        selectAll.checked = count > 0 && count === selectedRows.length;
        selectAll.indeterminate = count > 0 && count < selectedRows.length;
      };
      selectAll.addEventListener('change', () => { selectedRows.forEach(item => { item.checkbox.checked = selectAll.checked; }); syncBulkDelete(); });
      selectedRows.forEach(item => item.checkbox.addEventListener('change', syncBulkDelete));
      bulkDelete.addEventListener('click', async () => {
        const selected = selectedRows.filter(item => item.checkbox.checked);
        if (!selected.length || !window.confirm(`Delete ${selected.length} selected message${selected.length === 1 ? '' : 's'}?`)) return;
        bulkDelete.disabled = true;
        try {
          const deletedTargets = new Set();
          for (const item of selected) {
            // A broadcast can appear once for every recipient. Delete that
            // broadcast only once; subsequent identical requests would 404.
            if (deletedTargets.has(item.deleteForm.action)) continue;
            deletedTargets.add(item.deleteForm.action);
            const response = await fetch(item.deleteForm.action, {
              method: 'POST',
              body: new FormData(item.deleteForm),
              headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Could not delete every selected message.');
          }
          window.location.reload();
        } catch (error) {
          bulkDelete.disabled = false;
          window.alert(error.message || 'Selected messages could not be deleted.');
        }
      });
    }
  }

  const sentMessages = dialog.querySelector('.dashboard-sent-messages');
  if (sentMessages) {
    const sentRows = [...sentMessages.querySelectorAll('article')].map(article => {
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.className = 'dashboard-sent-select';
      checkbox.setAttribute('aria-label', 'Select this sent message');
      const messageContent = article.querySelector(':scope > div');
      messageContent.insertBefore(checkbox, messageContent.firstChild);
      const forms = article.querySelectorAll(':scope > form');
      const [resendForm, deleteForm] = forms;
      if (deleteForm) {
        resendForm.dataset.sentResend = 'true';
        deleteForm.dataset.sentDelete = 'true';
        const dismissForm = document.createElement('form');
        dismissForm.method = 'POST';
        dismissForm.action = `${deleteForm.action}/recall`;
        dismissForm.dataset.sentDismiss = 'true';
        dismissForm.onsubmit = () => window.confirm('Dismiss this message from recipients’ inboxes?');
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token) dismissForm.innerHTML = `<input type="hidden" name="_token" value="${token}">`;
        const dismissButton = document.createElement('button');
        dismissButton.type = 'submit';
        dismissButton.className = 'button secondary';
        dismissButton.textContent = 'Dismiss';
        dismissForm.append(dismissButton);
        // Keep the destructive delete action as the final action in every row.
        article.insertBefore(dismissForm, deleteForm);
      }
      return { article, checkbox, resendForm, deleteForm };
    }).filter(item => item.resendForm && item.deleteForm);

    if (sentRows.length) {
      const sentHeading = sentMessages.querySelector('h3');
      const selectAll = document.createElement('input');
      selectAll.type = 'checkbox';
      selectAll.className = 'dashboard-sent-select-all';
      selectAll.setAttribute('aria-label', 'Select all sent messages');
      selectAll.title = 'Select all sent messages';
      sentHeading.insertBefore(selectAll, sentHeading.firstChild);
      const actions = document.createElement('div');
      actions.className = 'dashboard-sent-bulk-actions';
      actions.innerHTML = '<button type="button" class="button secondary" data-sent-bulk-resend hidden title="Resend selected messages" aria-label="Resend selected messages">↻</button><button type="button" class="button secondary" data-sent-bulk-delete hidden title="Delete selected messages" aria-label="Delete selected messages">🗑</button>';
      sentMessages.querySelector('h3').insertAdjacentElement('afterend', actions);
      const resendButton = actions.querySelector('[data-sent-bulk-resend]');
      const deleteButton = actions.querySelector('[data-sent-bulk-delete]');
      const sync = () => {
        const selected = sentRows.filter(item => item.checkbox.checked);
        resendButton.hidden = selected.length === 0;
        deleteButton.hidden = selected.length === 0;
        resendButton.title = `Resend selected (${selected.length})`;
        deleteButton.title = `Delete selected (${selected.length})`;
        selectAll.checked = selected.length === sentRows.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < sentRows.length;
      };
      selectAll.addEventListener('change', () => {
        sentRows.forEach(item => { item.checkbox.checked = selectAll.checked; });
        sync();
      });
      sentRows.forEach(item => item.checkbox.addEventListener('change', sync));
      const submitSelected = async (kind, confirmation) => {
        const selected = sentRows.filter(item => item.checkbox.checked);
        if (!selected.length || !window.confirm(confirmation.replace('{count}', selected.length))) return;
        resendButton.disabled = true;
        deleteButton.disabled = true;
        try {
          for (const item of selected) {
            const form = kind === 'resend' ? item.resendForm : item.deleteForm;
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error(`Could not ${kind} every selected message.`);
          }
          window.location.reload();
        } catch (error) {
          resendButton.disabled = false;
          deleteButton.disabled = false;
          window.alert(error.message || 'Selected messages could not be updated.');
        }
      };
      resendButton.addEventListener('click', () => submitSelected('resend', 'Resend {count} selected message(s)?'));
      deleteButton.addEventListener('click', () => submitSelected('delete', 'Delete {count} selected message(s) for every recipient?'));
    }
  }

  const recipientForm = composer;
  if (!recipientForm) return;
  const allUsers = recipientForm.querySelector('[data-message-all-users]');
  const recipients = [...recipientForm.querySelectorAll('[data-message-recipient]')];
  const audience = recipientForm.querySelector('[data-message-audience]');
  const summary = recipientForm.querySelector('[data-message-recipient-summary]');
  const syncRecipients = () => {
    if (allUsers.checked) recipients.forEach(input => { input.checked = true; });
    const selected = recipients.filter(input => input.checked);
    audience.value = allUsers.checked ? 'all' : 'users';
    summary.textContent = allUsers.checked ? 'All active users' : `${selected.length} user${selected.length === 1 ? '' : 's'} selected`;
  };
  allUsers.addEventListener('change', () => {
    recipients.forEach(input => { input.checked = allUsers.checked; });
    syncRecipients();
  });
  recipients.forEach(input => input.addEventListener('change', () => {
    allUsers.checked = false;
    if (!recipients.some(option => option.checked)) allUsers.checked = true;
    syncRecipients();
  }));
  syncRecipients();
})();
