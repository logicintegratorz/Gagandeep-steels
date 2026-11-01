(() => {
 const addForm = document.querySelector('[data-directory-size-add]');
 if (addForm) {
  const product = addForm.querySelector('[data-add-size-product]');
  const sync = () => {
   addForm.action = product.value;
   const companies = JSON.parse(product.selectedOptions[0].dataset.companies || '[]');
   addForm.querySelector('[name=company]').value = companies[0] || '';
   document.getElementById('size-company-options').replaceChildren(...companies.map(name => { const option=document.createElement('option'); option.value=name; return option; }));
  };
  product.addEventListener('change',sync); sync();
 }
 const filters = document.querySelector('[data-all-size-filters]');
 const inputs = [...filters.querySelectorAll('[data-filter]')];
 const normalize = value => value.toLowerCase().replace(/\s+/g,'').replace(/×/g,'x');
 const refresh = () => {
  let count = 0;
  document.querySelectorAll('[data-company-size-group]').forEach(group => {
   let visible = 0;
   group.querySelectorAll('[data-all-size-row]').forEach(row => {
    row.hidden = !inputs.every(input => normalize(row.dataset[input.dataset.filter]).includes(normalize(input.value)));
    if (!row.hidden) visible++;
   });
   group.hidden = !visible; count += visible;
  });
  document.querySelector('[data-no-sizes]').hidden = count > 0;
 };
 filters.addEventListener('input', refresh);
 filters.querySelector('[data-clear-sizes]').addEventListener('click', () => { inputs.forEach(input => input.value = ''); refresh(); });
 refresh();
})();
