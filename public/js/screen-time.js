(() => {
  'use strict';

  const settings = document.getElementById('screen-time-settings');
  if (!settings) return;

  const endpoint = settings.dataset.heartbeatUrl;
  const token = settings.dataset.token;
  const intervalMs = 20000;
  let intervalId = null;
  let recording = false;

  const crmIsVisible = () => document.visibilityState === 'visible' && document.hasFocus();
  const payload = (state) => {
    const body = new FormData();
    body.append('_token', token);
    body.append('state', state);
    return body;
  };
  const send = (state, useBeacon = false) => {
    if (useBeacon && navigator.sendBeacon) {
      navigator.sendBeacon(endpoint, payload(state));
      return;
    }
    fetch(endpoint, {
      method: 'POST', credentials: 'same-origin', keepalive: true,
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: payload(state),
    }).catch(() => {});
  };
  const pause = () => {
    if (intervalId !== null) clearInterval(intervalId);
    intervalId = null;
    if (!recording) return;
    recording = false;
    send('pause', true);
  };
  const begin = () => {
    if (recording || !crmIsVisible()) return;
    recording = true;
    send('active');
    intervalId = window.setInterval(() => {
      if (!crmIsVisible()) return pause();
      send('active');
    }, intervalMs);
  };

  document.addEventListener('visibilitychange', () => crmIsVisible() ? begin() : pause());
  window.addEventListener('focus', begin);
  window.addEventListener('blur', pause);
  window.addEventListener('pagehide', pause);
  window.addEventListener('beforeunload', pause);
  begin();
})();
