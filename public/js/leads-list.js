document.addEventListener('DOMContentLoaded',()=>{
 const filters=document.querySelector('[data-auto-lead-filters]');
 if(filters){
  let timer;
  const apply=()=>{clearTimeout(timer);filters.requestSubmit();};
  filters.querySelector('input[name="q"]')?.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(apply,550);});
  filters.querySelectorAll('select').forEach(select=>select.addEventListener('change',apply));
 }
 const form=document.getElementById('lead-bulk-transfer');if(!form)return;
 const boxes=[...form.querySelectorAll('[data-lead-select]')];
 const all=document.getElementById('lead-select-all');
 const count=document.getElementById('lead-selection-count');
 const button=document.getElementById('lead-bulk-submit');
 const owner=document.getElementById('lead-bulk-owner');
 const refresh=()=>{
  const selected=boxes.filter(box=>box.checked).length;
  const transferableSelected=boxes.filter(box=>box.checked&&box.dataset.transferable==='true').length;
  count.value=selected;
  button.disabled=transferableSelected===0||!owner.value;
  all.checked=boxes.length>0&&selected===boxes.length;
  all.indeterminate=selected>0&&selected<boxes.length;
  boxes.forEach(box=>box.closest('tr').classList.toggle('is-selected',box.checked));
 };
 all.addEventListener('change',()=>{boxes.forEach(box=>box.checked=all.checked);refresh();});
 boxes.forEach(box=>box.addEventListener('change',refresh));
 owner.addEventListener('change',refresh);
 refresh();
});
