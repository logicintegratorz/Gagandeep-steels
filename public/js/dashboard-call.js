document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.querySelector('[data-dashboard-call-dialog]');
  if (!dialog) return;

  const form = dialog.querySelector('[data-dashboard-call-form]');
  const outcome = form.elements.outcome;
  const details = form.elements.details;
  const notes = dialog.querySelector('[data-dashboard-call-notes]');
  const exitButton = dialog.querySelector('[data-dashboard-call-exit]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  let opening = false;
  let activeLeadUrl = '';
  let activeTrigger = null;
  let activeContactId = '';

  const resetOutcome = () => {
    outcome.value = '';
    details.value = '';
    details.required = false;
    details.setCustomValidity('');
    notes.hidden = true;
    exitButton.hidden = true;
    form.dataset.submitting = 'false';
    dialog.querySelectorAll('[data-dashboard-call-answered], [data-dashboard-call-unanswered]').forEach(button => {
      button.classList.remove('primary');
      button.classList.add('secondary');
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-expanded', 'false');
    });
  };

  const selectOutcome = value => {
    if (outcome.value === value && !notes.hidden) {
      outcome.value = '';
      notes.hidden = true;
      exitButton.hidden = true;
      details.required = false;
      details.setCustomValidity('');
      dialog.querySelectorAll('[data-dashboard-call-answered], [data-dashboard-call-unanswered]').forEach(button => {
        button.classList.remove('primary');
        button.classList.add('secondary');
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-expanded', 'false');
      });
      return;
    }

    outcome.value = value;
    notes.hidden = false;
    details.required = value === 'Connected';
    exitButton.hidden = value !== 'Unanswered';
    details.setCustomValidity('');
    dialog.querySelector('[data-dashboard-call-details-label]').textContent = value === 'Connected' ? 'Call details *' : 'Call notes (optional)';
    details.placeholder = value === 'Connected' ? 'What was discussed? Add the outcome and next step.' : 'Add a retry or follow-up note if needed.';
    dialog.querySelector('[data-dashboard-call-note-help]').textContent = value === 'Connected' ? 'Add call details, then submit to save the call.' : 'Notes are optional. Submit saves the call and keeps you in Contacts.';
    dialog.querySelectorAll('[data-dashboard-call-answered], [data-dashboard-call-unanswered]').forEach(button => {
      const selected = button.hasAttribute(value === 'Connected' ? 'data-dashboard-call-answered' : 'data-dashboard-call-unanswered');
      button.classList.toggle('primary', selected);
      button.classList.toggle('secondary', !selected);
      button.setAttribute('aria-pressed', String(selected));
      button.setAttribute('aria-expanded', String(selected));
    });
  };

  document.addEventListener('click', async event => {
    const trigger = event.target.closest('[data-dashboard-call-open]');
    if (!trigger || opening) return;
    opening = true;
    try {
      const startData = new FormData();startData.append('dashboard', '1');
      const response = await fetch(trigger.dataset.callStartUrl, {method: 'POST', body: startData, credentials: 'same-origin', headers: {'X-CSRF-TOKEN': csrf || '', Accept: 'application/json'}});
      if (!response.ok) throw new Error('Could not start the call.');
      const startedCall = await response.json();
      activeLeadUrl = startedCall.lead_url || '';
      activeTrigger = trigger;
      activeContactId = trigger.closest('[data-contact-id]')?.dataset.contactId || '';
      resetOutcome();
      form.action = trigger.dataset.callOutcomeUrl;
      form.elements.request_key.value = crypto.randomUUID();
      dialog.querySelector('[data-dashboard-call-title]').textContent = `Call ${trigger.dataset.callName}`;
      const callUrl = `tel:${trigger.dataset.callPhone}`;
      const callLink = dialog.querySelector('[data-dashboard-call-link]');
      callLink.href = callUrl;
      callLink.setAttribute('aria-label', `Call ${trigger.dataset.callName}`);
      const number = dialog.querySelector('[data-dashboard-call-number]');
      number.href = callUrl;
      number.textContent = trigger.dataset.callPhone;
      const qr = dialog.querySelector('[data-dashboard-call-qr]');
      qr.src = trigger.dataset.callQrUrl;
      qr.alt = `QR code to call ${trigger.dataset.callPhone}`;
      dialog.showModal();
    } catch (error) {
      alert(error.message);
    } finally {
      opening = false;
    }
  });

  dialog.querySelector('[data-dashboard-call-close]').addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-dashboard-call-answered]').addEventListener('click', () => selectOutcome('Connected'));
  dialog.querySelector('[data-dashboard-call-unanswered]').addEventListener('click', () => selectOutcome('Unanswered'));
  dialog.addEventListener('cancel', event => {
    if (outcome.value !== 'Unanswered') return;
    event.preventDefault();
    if (form.dataset.submitting !== 'true') {
      form.requestSubmit(exitButton);
    }
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!outcome.value) {
      return;
    }
    if (outcome.value === 'Connected' && !details.value.trim()) {
      details.setCustomValidity('Enter call details before saving.');
      details.reportValidity();
      details.focus();
      return;
    }
    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';
    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {'X-CSRF-TOKEN': csrf || '', Accept: 'text/html'},
      });
      if (!response.ok) throw new Error('Could not save the call activity.');
      if (outcome.value === 'Connected' && activeLeadUrl) {
        const dashboardUrl = new URL(location.href);
        if (activeContactId) dashboardUrl.searchParams.set('contacts', activeContactId);
        dashboardUrl.hash = 'contacts-table';
        history.replaceState(history.state, '', dashboardUrl);

        const leadUrl = new URL(activeLeadUrl, location.href);
        leadUrl.searchParams.set('from', 'dashboard');
        location.assign(leadUrl);
        return;
      }
      dialog.close();
      resetOutcome();
      document.querySelector('.dash-contacts')?.scrollIntoView({behavior: 'smooth', block: 'start'});
      activeTrigger?.focus({preventScroll: true});
    } catch (error) {
      alert(error.message || 'Could not save the call activity.');
    } finally {
      form.dataset.submitting = 'false';
    }
  });
  details.addEventListener('input', () => details.setCustomValidity(''));
});
