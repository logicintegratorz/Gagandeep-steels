(() => {
    'use strict';
    document.querySelectorAll('[data-slot-directory]').forEach(checkbox => {
        checkbox.addEventListener('change', () => { checkbox.checked = false; window.location.assign(checkbox.dataset.slotDirectory); });
    });
    const clock = document.querySelector('[data-pricing-clock]');
    const formatter = new Intl.DateTimeFormat('en-GB', {timeZone: 'Asia/Kolkata', day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:false});
    const dayFormatter = new Intl.DateTimeFormat('en-CA', {timeZone:'Asia/Kolkata', year:'numeric', month:'2-digit', day:'2-digit'});
    const refreshRateAge = () => {
        const parts = Object.fromEntries(dayFormatter.formatToParts(new Date()).map(part => [part.type, part.value]));
        const today = `${parts.year}-${parts.month}-${parts.day}`;
        const reference = document.querySelector('[data-live-pricing]')?.dataset.livePricing === 'true'
            ? today : document.getElementById('pricing-date')?.value;
        document.querySelectorAll('[data-price-column]').forEach(column => {
            const option = column.querySelector('[data-company-select]').selectedOptions[0];
            const field = column.querySelector('.product-price-value');
            const stale = Boolean(option?.dataset.rateDay && reference && option.dataset.rateDay < reference);
            field.classList.toggle('rate-carried-forward', stale);
            if (stale) field.title = `Previous rate from ${option.dataset.effective}`;
            else field.removeAttribute('title');
        });
    };
    if (clock) {
        const tick = () => { clock.textContent = formatter.format(new Date()).replace(/,\s*/g, ' '); refreshRateAge(); };
        tick(); setInterval(tick, 1000);
    }
    const date = document.getElementById('pricing-date');
    if (date) {
        document.querySelector('[data-date-submit]').hidden = true;
        date.addEventListener('change', () => { if (date.value && date.checkValidity()) date.form.requestSubmit(); });
    }
    const showDetails = (column, scroll = false) => {
        const link = column.querySelector('[data-size-link]');
        const panel = document.getElementById(`product-detail-${link.dataset.productId}`);
        const option = column.querySelector('[data-company-select]').selectedOptions[0];
        document.querySelectorAll('[data-inline-detail]').forEach(item => { item.hidden = item !== panel; });
        document.querySelectorAll('[data-size-link]').forEach(item => item.setAttribute('aria-expanded', String(item === link)));

        panel.querySelectorAll('[data-company-activity]').forEach(item => { item.hidden = item.dataset.companyActivity !== (option.dataset.company || ''); });
        panel.querySelectorAll('[data-related-company]').forEach(item => { item.textContent = option.dataset.company || 'Unspecified company'; });
        panel.querySelectorAll('input[name=price]').forEach(input => { input.value = option.value; });
        panel.querySelectorAll('[data-size-manager]').forEach(workspace => {
            workspace.dataset.company = option.dataset.company || '';
            workspace.dataset.baseRate = option.dataset.amount;
            const displayedPrice = workspace.querySelector('[data-related-product-price]');
            if (displayedPrice) displayedPrice.textContent = option.dataset.amount;
            workspace.dispatchEvent(new Event('ratechange'));
        });
        if (scroll) panel.scrollIntoView({behavior:'smooth', block:'start'});
    };
    document.querySelectorAll('[data-summary-rate-trigger]').forEach(trigger => {
        const open = () => {
            const link = [...document.querySelectorAll('[data-size-link]')].find(item => item.dataset.productId === trigger.dataset.summaryRateTrigger);
            link?.closest('[data-price-column]').querySelector('[data-rate-trigger]')?.click();
        };
        trigger.addEventListener('click', open);
        trigger.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(); } });
    });
    document.querySelectorAll('[data-size-link]').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            showDetails(link.closest('[data-price-column]'), true);
        });
    });
    document.querySelectorAll('[data-company-select]').forEach(select => {
        select.addEventListener('change', () => {
            const option = select.selectedOptions[0];
            refreshRateAge();
            const column = select.closest('[data-price-column]');
            column.querySelector('[data-price-output]').textContent = option.dataset.amount;
            const dated = column.querySelector('[data-price-date]'); if (dated) dated.textContent = option.dataset.effective;
            const basis = column.querySelector('[data-price-basis]'); if (basis) basis.textContent = option.dataset.basis;
            const edit = column.querySelector('[data-price-edit]');
            const sizes = column.querySelector('[data-size-link]');
            if (sizes) { const url = new URL(sizes.href); url.searchParams.set('price', option.value); sizes.href = url; }
            if (sizes?.getAttribute('aria-expanded') === 'true') showDetails(column);
            if (edit) { const url = new URL(edit.href); url.searchParams.set('price', option.value); edit.href = url; }
            document.querySelector('[data-pricing-status]').textContent = `${column.querySelector('h3').textContent}: ${option.textContent}, ${option.dataset.amount}.`;
        });
    });
    document.addEventListener('DOMContentLoaded', () => {
        const panel = document.querySelector('[data-reopen]:not([data-reopen=""])');
        if (!panel) return;
        const link = [...document.querySelectorAll('[data-size-link]')].find(item => item.dataset.productId === panel.dataset.inlineDetail);
        if (!link) return;
        const column = link.closest('[data-price-column]');
        const company = column.querySelector('[data-company-select]');
        if ([...company.options].some(option => option.value === panel.dataset.reopen)) company.value = panel.dataset.reopen;
        company.dispatchEvent(new Event('change'));
        showDetails(column, true);

    });
})();
