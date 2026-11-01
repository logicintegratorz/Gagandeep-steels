document.addEventListener('DOMContentLoaded',()=>{
 const queue=document.getElementById('whatsapp-queue');
 const rows=[...document.querySelectorAll('[data-whatsapp-contact]')];
 if(!queue||!rows.length)return;
 const next=document.getElementById('whatsapp-next');
 const count=document.getElementById('whatsapp-opened-count');
 const label=document.getElementById('whatsapp-next-label');
 const reset=document.getElementById('whatsapp-reset');
 const key=queue.dataset.queueKey;
 const validIds=new Set(rows.map(row=>row.dataset.whatsappId));
 let saved=[];
 try{saved=JSON.parse(sessionStorage.getItem(key)||'[]');}catch{}
 const opened=new Set(Array.isArray(saved)?saved.filter(id=>validIds.has(String(id))).map(String):[]);
 const save=()=>{try{sessionStorage.setItem(key,JSON.stringify([...opened]));}catch{}};
 const refresh=()=>{
  const pending=rows.find(row=>!opened.has(row.dataset.whatsappId));
  count.value=opened.size;
  reset.hidden=opened.size===0;
  if(pending){
   const name=pending.querySelector('strong').textContent.trim();
   label.textContent=`Next contact: ${name}`;
   next.href=pending.dataset.whatsappUrl;
   next.textContent=`Open ${name} chat ↗`;
   next.setAttribute('aria-label',`Open WhatsApp chat for ${name}`);
   next.classList.remove('is-disabled');
  }else{
   label.textContent='All chats opened. Check WhatsApp to confirm each message was sent.';
   next.removeAttribute('href');
   next.textContent='All chats opened';
   next.classList.add('is-disabled');
  }
  rows.forEach(row=>{
   const isOpen=opened.has(row.dataset.whatsappId);
   row.classList.toggle('whatsapp-opened',isOpen);
   row.classList.toggle('whatsapp-current',row===pending);
   row.querySelector('[data-whatsapp-status]').textContent=isOpen?'Chat opened — send status unknown':row===pending?'Next':'Not opened';
  });
 };
 const mark=row=>{opened.add(row.dataset.whatsappId);save();refresh();};
 next.addEventListener('click',event=>{const row=rows.find(item=>!opened.has(item.dataset.whatsappId));if(!row){event.preventDefault();return;}setTimeout(()=>mark(row),0);});
 rows.forEach(row=>row.querySelector('[data-whatsapp-open]').addEventListener('click',()=>mark(row)));
 reset.addEventListener('click',()=>{opened.clear();save();refresh();});
 refresh();
});
