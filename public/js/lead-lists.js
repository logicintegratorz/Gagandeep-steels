document.addEventListener('DOMContentLoaded',()=>{
 const listDialog=document.getElementById('create-list');
 if(listDialog){
  const open=()=>{if(!listDialog.open)listDialog.showModal();};
  document.querySelectorAll('[data-open-list-dialog]').forEach(button=>button.addEventListener('click',open));
  listDialog.querySelectorAll('[data-close-list-dialog]').forEach(button=>button.addEventListener('click',()=>listDialog.close()));
  listDialog.addEventListener('click',event=>{if(event.target===listDialog)listDialog.close();});
  if(listDialog.hasAttribute('data-open-on-load')||location.hash==='#create-list')open();
 }
 const search=document.querySelector('[data-list-search]'),status=document.querySelector('[data-list-status]');
 if(search&&status){const rows=[...document.querySelectorAll('[data-list-row]')],empty=document.querySelector('[data-list-empty]');
  const refresh=()=>{const term=search.value.trim().toLocaleLowerCase();let visible=0;rows.forEach(row=>{const matches=row.dataset.listName.includes(term)&&(status.value==='all'||row.dataset.listStatus===status.value);row.hidden=!matches;if(matches)visible++;});if(empty){empty.hidden=visible>0;empty.textContent=rows.length?'No lists match this search.':'No lists yet. Use New List to create one.';}};
  search.addEventListener('input',refresh);status.addEventListener('change',refresh);refresh();
 }
 const form=document.getElementById('list-send-form');
 if(form&&document.getElementById('list-selected-count')){const checks=[...form.querySelectorAll('[data-list-select]')],all=document.getElementById('list-select-all'),count=document.getElementById('list-selected-count'),button=document.getElementById('list-send-button'),channel=document.getElementById('list-channel');
  const refresh=()=>{const n=checks.filter(c=>c.checked).length;count.value=n;button.disabled=n===0||n>50;button.title=n>50?'Select up to 50, or use Send to all below':'';all.checked=n>0&&n===checks.length;all.indeterminate=n>0&&n<checks.length;checks.forEach(c=>c.closest('tr').classList.toggle('is-selected',c.checked));};
  checks.forEach(c=>c.addEventListener('change',refresh));all.addEventListener('change',()=>{checks.forEach(c=>c.checked=all.checked);refresh();});
  form.addEventListener('submit',e=>{const message=channel.value==='WhatsApp'?`Open WhatsApp chats for ${count.value} selected contact(s)? You will press Send in each chat.`:`Send the saved list email to ${count.value} selected contact(s)?`;if(!confirm(message))e.preventDefault();else button.disabled=true;});refresh();
 }
 const frequency=document.getElementById('schedule-frequency');if(frequency){const refresh=()=>{document.getElementById('schedule-week').hidden=frequency.value!=='Weekly';document.getElementById('schedule-month').hidden=frequency.value!=='Monthly';};frequency.addEventListener('change',refresh);refresh();}
 const menus=[...document.querySelectorAll('.list-action-menu')];
 const position=menu=>{const anchor=menu.querySelector('summary').getBoundingClientRect(),popover=menu.querySelector('.list-action-popover');const width=popover.offsetWidth,height=popover.offsetHeight;popover.style.left=`${Math.max(10,Math.min(innerWidth-width-10,anchor.right-width))}px`;popover.style.top=`${anchor.bottom+height+10<=innerHeight?anchor.bottom+5:Math.max(10,anchor.top-height-5)}px`;};
 menus.forEach(menu=>menu.addEventListener('toggle',()=>{if(!menu.open)return;menus.forEach(other=>{if(other!==menu)other.open=false;});position(menu);}));
 document.addEventListener('click',event=>{if(!event.target.closest('.list-action-menu'))menus.forEach(menu=>menu.open=false);});
 document.addEventListener('keydown',event=>{if(event.key==='Escape')menus.forEach(menu=>menu.open=false);});
 window.addEventListener('scroll',()=>menus.forEach(menu=>menu.open=false),true);
 document.querySelectorAll('[data-open-list-editor]').forEach(link=>link.addEventListener('click',()=>{document.getElementById('list-editor').open=true;}));
});
