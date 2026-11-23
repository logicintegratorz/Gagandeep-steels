(() => {
    'use strict';

    const form = document.querySelector('[data-contact-search-form]');
    const input = form?.querySelector('[data-contact-search-input]');
    if (!form || !input) return;

    let timer;
    const submit = () => form.requestSubmit();

    input.addEventListener('input', event => {
        clearTimeout(timer);
        if (event.isComposing) return;
        timer = window.setTimeout(submit, 400);
    });
    input.addEventListener('compositionend', () => {
        clearTimeout(timer);
        timer = window.setTimeout(submit, 400);
    });
    form.addEventListener('submit', () => clearTimeout(timer));
})();
