(() => {
    document.querySelectorAll('[data-price-column]').forEach(column => {
        const select = column.querySelector('[data-company-select]');
        const picker = column.querySelector('[data-company-picker]');
        if (!picker) return;
        select.parentElement.hidden = true;
        picker.hidden = false;
        picker.setAttribute('name', 'product-company-rates');
        picker.querySelector('summary').addEventListener('click', () => {
            document.querySelectorAll('[data-company-picker]').forEach(other => {
                if (other !== picker) other.open = false;
            });
        });
        const caption = () => {
            picker.querySelector('[data-company-caption]').textContent = select.selectedOptions[0]?.dataset.company || 'Unspecified company';
            picker.querySelectorAll('[data-company-choice]').forEach(button => {
                button.setAttribute('aria-pressed', String(button.dataset.companyChoice === select.value));
                button.title = button.textContent;
            });
        };
        select.addEventListener('change', caption);
        picker.querySelectorAll('[data-company-choice]').forEach(button => button.addEventListener('click', () => {
            select.value = button.dataset.companyChoice;
            select.dispatchEvent(new Event('change'));
            picker.open = false;
            picker.querySelector('summary').focus();
        }));
        const rateTrigger = column.querySelector('[data-rate-trigger]');
        const dialog = column.querySelector('.company-price-dialog');
        const openEditor = () => {
            if (!dialog) return;
            picker.open = false;
            dialog.querySelectorAll('[data-price-id]').forEach(row => {
                row.querySelector('input[type=radio]').checked = row.dataset.priceId === select.value;
            });
            dialog.showModal();
            rateTrigger?.setAttribute('aria-expanded', 'true');
        };
        rateTrigger?.addEventListener('click', openEditor);
        rateTrigger?.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openEditor(); }
        });
        dialog?.addEventListener('close', () => {
            rateTrigger?.setAttribute('aria-expanded', 'false');
            rateTrigger?.focus();
        });
        picker.addEventListener('keydown', event => {
            if (event.key === 'Escape') { picker.open = false; rateTrigger?.focus(); }
        });
        caption();
    });
    document.addEventListener('click', event => {
        document.querySelectorAll('[data-company-picker][open]').forEach(picker => {
            if (!picker.contains(event.target)) picker.open = false;
        });
    });
    document.querySelectorAll('.company-price-dialog').forEach(dialog => {
        const rows = dialog.querySelector('[data-company-rows]');
        const original = rows.innerHTML;
        let next = rows.children.length;
        const measure = document.createElement('canvas').getContext('2d');
        const resizeDialog = () => {
            const widths = [...rows.querySelectorAll('.company-edit-row')].map(row => parseFloat(row.style.getPropertyValue('--company-field-width')) || 120);
            dialog.style.setProperty('--company-dialog-width', `${Math.max(360, Math.max(120, ...widths) + 260)}px`);
        };
        const resizeCompany = input => {
            const style = getComputedStyle(input);
            measure.font = `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
            const width = Math.ceil(measure.measureText(input.value || input.placeholder).width) + 30;
            input.closest('.company-edit-row').style.setProperty('--company-field-width', `${Math.max(120, width)}px`);
            resizeDialog();
        };
        rows.addEventListener('input', event => {
            if (event.target.matches('input[name$="[company]"]')) resizeCompany(event.target);
        });
        const update = () => {
            rows.querySelectorAll('input[name$="[company]"]').forEach(resizeCompany);
            rows.querySelectorAll('[data-company-remove]').forEach(button => { button.disabled = rows.children.length <= 1; });
            if (!rows.querySelector('input[type=radio]:checked')) rows.querySelector('input[type=radio]').checked = true;
        };
        dialog.querySelector('[data-company-add]').addEventListener('click', () => {
            const fragment = dialog.querySelector('template').content.cloneNode(true);
            fragment.querySelector('input[type=radio]').value = next;
            fragment.querySelectorAll('input[data-field]').forEach(input => { input.name = `rows[${next}][${input.dataset.field}]`; });
            next++;
            rows.append(fragment); update();
            rows.lastElementChild.querySelector('input[type=radio]').checked = true;
            rows.lastElementChild.querySelector('input[type=text]').focus();
        });
        rows.addEventListener('click', event => {
            const button = event.target.closest('[data-company-remove]');
            if (button && rows.children.length > 1) { button.closest('.company-edit-row').remove(); update(); }
            if (!button) {
                const row = event.target.closest('.company-edit-row');
                if (row) row.querySelector('input[type=radio]').checked = true;
            }
        });
        dialog.querySelector('[data-company-cancel]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => { rows.innerHTML = original; next = rows.children.length; update(); });
        update();
    });
})();
