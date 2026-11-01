document.querySelectorAll('[data-rate-log-filters]').forEach(form => {
    let timer, controller;
    form.querySelector('[data-log-date]')?.addEventListener('click', event => {
        try { event.currentTarget.showPicker?.(); } catch (_) { /* Native picker remains available. */ }
    });
    const refresh = async () => {
        if (!form.reportValidity()) return;
        controller?.abort();
        controller = new AbortController();
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        const results = document.querySelector('[data-rate-log-results]');
        results.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, { signal: controller.signal });
            if (!response.ok) throw new Error('Unable to load rates');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = page.querySelector('[data-rate-log-results]');
            if (!next) throw new Error('Missing results');
            results.replaceWith(next);
            history.replaceState(null, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        }
    };
    form.addEventListener('input', () => {
        controller?.abort();
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);
    });
    form.addEventListener('change', () => {
        clearTimeout(timer);
        refresh();
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        clearTimeout(timer);
        refresh();
    });
});
