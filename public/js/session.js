(() => {
  'use strict';
  const config = document.getElementById('session-settings');
  if (!config) return;
  let idle = Number(config.dataset.idle), midnight = Number(config.dataset.midnight);
  let offset = Number(config.dataset.serverNow) * 1000 - Date.now();
  let lastSent = 0, busy = false, exited = false;
  const now = () => (Date.now() + offset) / 1000;
  const channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('crm-session') : null;
  const exit = (broadcast = true) => {
    if (exited) return;
    exited = true;
    try { sessionStorage.setItem('crm-session-notice', 'Your session ended. Please sign in again. Sessions expire at midnight or after 30 minutes of inactivity.'); } catch {}
    if (broadcast) channel?.postMessage({ logout: true });
    document.body.replaceChildren();
    location.replace(config.dataset.loginUrl);
  };
  const sync = async (activity = false) => {
    if (busy || exited) return;
    busy = true;
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    try {
      const response = await fetch(activity ? config.dataset.activityUrl : config.dataset.statusUrl, {
        method: activity ? 'POST' : 'GET', cache: 'no-store', credentials: 'same-origin', signal: controller.signal,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
      });
      if (response.status === 401 || response.status === 419 || response.redirected) { exit(); return; }
      if (!response.ok) throw new Error('Session check unavailable');
      const data = await response.json();
      idle = data.idleExpiresAt; midnight = data.midnightExpiresAt; offset = data.serverNow * 1000 - Date.now();
      channel?.postMessage({ idle, midnight, offset });
    } catch {
      // Do not leave a sensitive page visible beyond its last confirmed deadline.
      if (now() >= Math.min(idle, midnight)) exit();
    } finally { clearTimeout(timeout); busy = false; }
  };
  channel?.addEventListener('message', event => {
    if (event.data.logout) exit(false);
    else if (Number.isFinite(event.data.idle) && Number.isFinite(event.data.midnight)) {
      idle = event.data.idle; midnight = event.data.midnight; offset = event.data.offset;
    }
  });
  const activity = () => {
    if (document.visibilityState !== 'visible' || Date.now() - lastSent < 30000) return;
    lastSent = Date.now(); sync(true);
  };
  ['pointerdown', 'pointermove', 'keydown', 'input', 'scroll', 'touchstart'].forEach(event => document.addEventListener(event, activity, { passive: true, capture: true }));
  document.querySelectorAll('form[action$="/logout"]').forEach(form => form.addEventListener('submit', () => channel?.postMessage({logout:true})));
  setInterval(() => { if (now() >= midnight) exit(); else if (now() >= idle) sync(); }, 1000);
  setInterval(() => sync(), 30000);
  window.addEventListener('pageshow', () => sync());
  document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') sync(); });
})();
