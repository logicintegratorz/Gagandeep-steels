(() => {
    'use strict';
    // Decimal arithmetic keeps free-text inputs intact and avoids floating point rounding.
    const addRates = (left, right) => {
        const parse = value => {
            const text = String(value).trim();
            if (!/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/.test(text) || text.length > 200) return null;
            const negative = text.startsWith('-');
            const [whole, fraction = ''] = text.replace(/^[+-]/, '').split('.');
            return {value: BigInt((whole || '0') + fraction) * (negative ? -1n : 1n), scale: fraction.length};
        };
        const a = parse(left), b = parse(right);
        if (!a || !b) return null;
        const scale = Math.max(a.scale, b.scale);
        const sum = a.value * 10n ** BigInt(scale-a.scale) + b.value * 10n ** BigInt(scale-b.scale);
        const sign = sum < 0n ? '-' : '';
        const digits = (sum < 0n ? -sum : sum).toString().padStart(scale+1, '0');
        return sign + (scale ? `${digits.slice(0,-scale)}.${digits.slice(-scale)}`.replace(/\.?0+$/, '') : digits);
    };
    const sizeFamily = value => String(value).trim().toLowerCase().split(/\s*[x×*]\s*|\s+/u)[0];
    const relatedSize = (selected, candidate) => Boolean(sizeFamily(selected)) && sizeFamily(selected) === sizeFamily(candidate);
    const normalizeSizeSearch = value => String(value).replace(/[^0-9]/g, 'X').replace(/X+/g, 'X');
    if (typeof module !== 'undefined' && module.exports) module.exports = {addRates, relatedSize, normalizeSizeSearch};
    if (typeof document === 'undefined') return;
    document.querySelectorAll('[data-size-manager]').forEach(manager => {
        manager.querySelectorAll('[data-size-price-tab]').forEach(button => button.addEventListener('click', () => {
            const mode = button.dataset.sizePriceTab;
            manager.querySelectorAll('[data-size-price-tab]').forEach(tab => tab.setAttribute('aria-pressed', String(tab === button)));
            manager.querySelector('[data-location-price-heading]').textContent = mode === 'manufacturer' ? 'Manufacturer/Seller' : 'Godown Price';
            manager.querySelectorAll('[data-location-price]').forEach(cell => { cell.textContent = mode === 'godown' ? manager.dataset.baseRate || '—' : cell.dataset.manufacturer || '—'; });
        }));
        const search = manager.querySelector('[data-size-list-search]');
        const picker = manager.querySelector('[data-size-picker]');
        if (picker) {
            const pickerSearch = manager.querySelector('[data-size-picker-search]');
            const includeCC = manager.querySelector('[data-include-cc]');
            const options = [...picker.options].filter(option => option.value);

            const companyCC = new Set();
            const companyResults = manager.querySelector('[data-size-company-results]');
            const renderCompanies = () => {
                if (!companyResults) return;
                const term = normalizeSizeSearch(pickerSearch.value);
                companyResults.hidden = !term;
                const body = manager.querySelector('[data-size-company-rows]');
                body.replaceChildren();
                if (!term) return;
                const productId = manager.closest('[data-inline-detail]')?.dataset.inlineDetail;
                const link = [...document.querySelectorAll('[data-size-link]')].find(item => item.dataset.productId === productId);
                const rates = [...(link?.closest('[data-price-column]').querySelector('[data-company-select]')?.options || [])];
                const matches = options.filter(option => normalizeSizeSearch(option.textContent.trim()).startsWith(term));
                matches.forEach(option => {
                    const rate = rates.find(rate => (rate.dataset.company || '') === (option.dataset.company || ''));
                    const basic = rate?.dataset.amount || ((option.dataset.company || '') === (manager.dataset.company || '') ? manager.dataset.baseRate : '');
                    const row = document.createElement('tr');
                    const values = [option.dataset.company || 'Unspecified company', option.textContent, basic || '—', option.dataset.difference || '—', option.dataset.cc || '—', addRates(basic,option.dataset.difference) ?? 'Not calculable', (option.dataset.weight || '').replace(/ kg\/mtr - /,' kg/mtr\n'), option.dataset.notes || ''];
                    const fields = [];
                    const labels = ['Company name','Size','Basic Price','Basic difference','CC','Size price','Weight','Notes'];
                    values.forEach((value,index) => {
                        const cell = document.createElement('td');
                        const field = document.createElement(index === 6 ? 'textarea' : 'input');
                        if (index === 6) { field.rows = 2; field.className = 'weight-readonly'; }
                        else field.type = 'text';
                        field.readOnly = true; field.value = value;
                        field.setAttribute('aria-label', `${labels[index]} for ${option.dataset.company || 'Unspecified company'} ${option.textContent}`);
                        fields.push(field);
                        if (index === 4) {
                            const wrapper = document.createElement('label'); wrapper.className = 'size-cc-toggle';
                            const toggle = document.createElement('input'); toggle.type = 'checkbox';
                            toggle.checked = companyCC.has(option.value);
                            toggle.setAttribute('aria-label', `Include CC for ${option.dataset.company || 'Unspecified company'} ${option.textContent}`);
                            toggle.addEventListener('change', () => {
                                if (toggle.checked) companyCC.add(option.value); else companyCC.delete(option.value);
                                updatePrice();
                            });
                            wrapper.append(toggle,field); cell.append(wrapper);
                        } else cell.append(field);
                        row.append(cell);
                    });
                    const updatePrice = () => {
                        let total = addRates(basic,option.dataset.difference);
                        if (companyCC.has(option.value) && total !== null) total = addRates(total,option.dataset.cc);
                        fields[5].value = total ?? 'Not calculable';
                    };
                    updatePrice();
                    body.append(row);
                });
                manager.querySelector('[data-size-company-empty]').hidden = matches.length > 0;
            };
            const fillPicked = () => {
                const basicField = manager.querySelector('[data-picked-basic]');
                if (basicField) basicField.value = manager.dataset.baseRate || '';
                renderCompanies();
                const option = picker.selectedOptions[0];
                const chosen = option?.value && (manager.dataset.company === undefined || option.dataset.company === manager.dataset.company) ? option : null;
                const savedList = manager.nextElementSibling;
                savedList?.querySelectorAll('[data-size-list-row]').forEach(row => {
                    const matches = Boolean(chosen && relatedSize(chosen.textContent, row.dataset.size));
                    row.classList.toggle('size-match-highlight', matches);
                });
                for (const key of ['difference','cc','weight','notes']) manager.querySelector(`[data-picked-${key}]`).value = key === 'weight' ? (chosen?.dataset[key] || '').replace(/ kg\/mtr - /, ' kg/mtr\n') : chosen?.dataset[key] || '';
                let price = chosen ? addRates(manager.dataset.baseRate, chosen.dataset.difference) : null;
                if (includeCC) {
                    includeCC.disabled = !chosen;
                    if (!chosen) includeCC.checked = false;
                    if (chosen && includeCC.checked && price !== null) price = addRates(price, chosen.dataset.cc);
                }
                manager.querySelector('[data-picked-price]').value = chosen ? price ?? 'Not calculable' : '';
            };
            pickerSearch.addEventListener('input', () => {
                const cursor = pickerSearch.selectionStart;
                const before = pickerSearch.value;
                const term = normalizeSizeSearch(before);
                pickerSearch.value = term;
                if (cursor !== null) {
                    const position = normalizeSizeSearch(before.slice(0,cursor)).length;
                    pickerSearch.setSelectionRange(position,position);
                }
                const matching = term ? options.filter(option => (manager.dataset.company === undefined || option.dataset.company === manager.dataset.company) && normalizeSizeSearch(option.textContent.trim()).startsWith(term)) : [];
                const exact = matching.filter(option => normalizeSizeSearch(option.textContent.trim()) === term);
                const chosen = exact.length === 1 ? exact[0] : matching.length === 1 ? matching[0] : null;
                picker.value = chosen?.value || '';
                manager.querySelector('[data-size-picker-status]').textContent = !term || chosen ? '' : matching.length ? 'Enter the complete size to load its values.' : 'No matching sizes.';
                fillPicked();
                manager.nextElementSibling?.querySelectorAll('[data-size-list-row]').forEach(row => {
                    row.classList.toggle('size-match-highlight', Boolean(term && normalizeSizeSearch(row.dataset.size.trim()).startsWith(term)));
                });
            });
            includeCC?.addEventListener('change', fillPicked);
            manager.addEventListener('ratechange', () => {
                if (pickerSearch.value) pickerSearch.dispatchEvent(new Event('input'));
                else fillPicked();
            });
        }
        const rows = [...manager.querySelectorAll('[data-size-list-row]')];
        const refresh = () => {
            if (manager.querySelector('[data-size-price-tab="godown"][aria-pressed="true"]')) {
                manager.querySelectorAll('[data-location-price]').forEach(cell => { cell.textContent = manager.dataset.baseRate || '—'; });
            }
            const term = (search?.value ?? '').trim().toLowerCase().replace(/\s/g, '').replace(/×/g, 'x');
            let count = 0;
            rows.forEach(row => {
                row.hidden = (manager.dataset.company !== undefined && row.dataset.company !== manager.dataset.company) || !row.dataset.size.toLowerCase().replace(/\s/g, '').replace(/×/g, 'x').startsWith(term);
                if (!row.hidden) count++;
                const priceCell = row.querySelector('[data-size-list-price]');
                if (priceCell) priceCell.textContent = addRates(manager.dataset.baseRate, row.dataset.difference) ?? 'Not calculable';
            });
            const empty = manager.querySelector('[data-size-list-empty]');
            if (empty) empty.hidden = count > 0;
            const results = manager.querySelector('[data-related-results]');
            if (results) results.hidden = !term || count === 0;
            const counter = manager.querySelector('[data-size-list-count]');
            if (counter) counter.textContent = manager.hasAttribute('data-search-required') && !term ? '' : (count ? `${count} sizes` : 'No matching sizes.');
        };
        search?.addEventListener('input', refresh);
        manager.addEventListener('ratechange', refresh);
        refresh();
    });
    document.querySelectorAll('[data-size-workspace]').forEach(workspace => {
    const search = workspace.querySelector('[data-size-search]');
    const select = workspace.querySelector('[data-size-select]');
    const options = [...select.options].filter(option => option.value);
    const status = workspace.querySelector('[data-size-status]');
    const normalize = text => text.trim().toLocaleLowerCase();
    const fill = row => {
        const related = workspace.querySelector('[data-related-sizes]');
        let count = 0;
        workspace.querySelectorAll('[data-related-row]').forEach(item => {
            item.hidden = !row || !relatedSize(row.dataset.size, item.dataset.size);
            if (!item.hidden) {
                count++;
                item.querySelector('[data-related-price]').textContent = addRates(workspace.dataset.baseRate, item.dataset.difference) ?? 'Not calculable';
            }
        });
        related.hidden = !row;
        workspace.querySelector('[data-related-count]').textContent = `${count} saved sizes`;
        for (const key of ['difference','weight','cc']) workspace.querySelector(`[data-size-${key}]`).value = key === 'weight' ? (row?.dataset[key] || '').replace(/ kg\/mtr - /, ' kg/mtr\n') : row?.dataset[key] || '';
        workspace.querySelector('[data-size-base-price]').value = workspace.dataset.baseRate || '';
        const sum = row ? addRates(workspace.dataset.baseRate, row.dataset.difference) : null;
        workspace.querySelector('[data-size-price]').value = sum ?? (row ? 'Not calculable' : '');
        status.textContent = row ? (sum === null ? 'Product price or basic difference is missing or contains text. Size price cannot be calculated.' : 'Size price = product price + basic difference.') : 'Select a saved size to load its details.';
    };
    const filter = () => {
        const term = normalize(search.value);
        const current = select.value;
        const matches = options.filter(option => normalize(option.dataset.size).startsWith(term));
        select.replaceChildren(select.options[0], ...matches);
        select.value = matches.some(option => option.value === current) ? current : '';
        fill(options.find(option => option.value === select.value));
        if (!matches.length) status.textContent = 'No matching sizes. Try another search.';
    };
    search.addEventListener('input', filter);
    select.addEventListener('change', () => fill(options.find(option => option.value === select.value)));
    workspace.addEventListener('ratechange', () => fill(options.find(option => option.value === select.value)));
    });
})();
