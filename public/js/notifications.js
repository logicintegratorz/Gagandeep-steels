(() => {
  'use strict';
  const bell = document.querySelector('[data-notification-bell]');
  if (!bell) return;
  let busy = false;
  let latest = Number(bell.dataset.latestMessage || 0);
  let audio;
  const unlock = () => {
    const Context = window.AudioContext || window.webkitAudioContext;
    if (!Context) return;
    audio ||= new Context();
    if (audio.state === 'suspended') audio.resume().catch(() => {});
  };
  document.addEventListener('pointerdown', unlock);
  document.addEventListener('keydown', unlock);
  const chime = () => {
    if (!audio || audio.state !== 'running') return;
    [660,880].forEach((frequency,index) => {
      const oscillator=audio.createOscillator(), volume=audio.createGain();
      const start=audio.currentTime+index*.18;
      oscillator.frequency.value=frequency;
      volume.gain.setValueAtTime(0,start);
      volume.gain.linearRampToValueAtTime(.12,start+.02);
      volume.gain.exponentialRampToValueAtTime(.001,start+.3);
      oscillator.connect(volume); volume.connect(audio.destination);
      oscillator.start(start); oscillator.stop(start+.32);
    });
  };
  const refresh = async () => {
    if (busy || document.hidden) return;
    busy = true;
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    try {
      const response = await fetch(bell.dataset.countUrl, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: controller.signal });
      if (!response.ok || response.redirected) return;
      const data = await response.json();
      if (!Number.isInteger(data.unread) || data.unread < 0) return;
      if (Number.isInteger(data.latest_message)) {
        if (data.latest_message > latest && data.sound_enabled) chime();
        latest = Math.max(latest, data.latest_message);
      }
      const badge = bell.querySelector('.notification-count');
      badge.textContent = data.unread > 99 ? '99+' : String(data.unread);
      badge.hidden = !data.unread;
      bell.setAttribute('aria-label', 'Notifications, ' + data.unread + ' unread');
    } catch { /* Keep the last confirmed count; background failures are not user actions. */ }
    finally { clearTimeout(timeout); busy = false; }
  };
  setInterval(refresh, 10000);
  document.addEventListener('visibilitychange', refresh);
})();
