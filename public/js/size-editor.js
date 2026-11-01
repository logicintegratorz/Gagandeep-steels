(() => {
    'use strict';
    document.querySelectorAll('[data-size-add-open]').forEach(button => button.addEventListener('click', () => {
        document.getElementById(button.dataset.sizeAddOpen).showModal();
    }));
    document.querySelectorAll('[data-size-add-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    document.querySelectorAll('[data-size-manager]').forEach(manager => {
        const close = panel => {
            panel.hidden = true;
            panel.querySelector('form').reset();
            manager.querySelectorAll('[data-size-edit-open]').forEach(button => {
                if (button.dataset.sizeEditOpen === panel.id) button.setAttribute('aria-expanded', 'false');
            });
        };
        manager.querySelectorAll('[data-size-edit-open]').forEach(button => {
            button.addEventListener('click', () => {
                const panel = document.getElementById(button.dataset.sizeEditOpen);
                manager.querySelectorAll('[data-size-editor]').forEach(other => {
                    if (other !== panel) close(other);
                });
                panel.hidden = false;
                button.setAttribute('aria-expanded', 'true');
                panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
                panel.querySelector('input[name=size]').focus({preventScroll: true});
            });
        });
        manager.querySelectorAll('[data-size-edit-cancel]').forEach(button => {
            button.addEventListener('click', () => {
                const panel = button.closest('[data-size-editor]');
                close(panel);
                manager.querySelector(`[data-size-edit-open="${panel.id}"]`).focus();
            });
        });
    });
})();
