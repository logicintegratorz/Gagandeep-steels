// Restore lead-page position after server-rendered actions, filters and reloads.
document.addEventListener('DOMContentLoaded',()=>{
 const stageForm=document.getElementById('lead-stage-form');
 const profile=document.querySelector('[data-lead-state]');if(!profile)return;
 const followups=document.getElementById('lead-followups');
 if(followups){if(location.hash==='#lead-followups')followups.open=true;document.querySelector('a[href="#lead-followups"]')?.addEventListener('click',()=>{followups.open=true;});}
 const trackVisit=profile.dataset.trackVisit==='1';
 const visitKey=trackVisit?crypto.randomUUID():'';
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
 const visit=event=>{if(!csrf)return;const payload=new FormData();payload.append('_token',csrf);payload.append('session_key',visitKey);payload.append('event',event);if(event==='closed'){navigator.sendBeacon?.(profile.dataset.visitUrl,payload);return;}fetch(profile.dataset.visitUrl,{method:'POST',body:payload,credentials:'same-origin',keepalive:true}).catch(()=>{});};
 if(trackVisit)visit('opened');
 const heartbeat=trackVisit?setInterval(()=>{if(!document.hidden)visit('heartbeat');},30000):null;
 window.addEventListener('pagehide',()=>{if(trackVisit){clearInterval(heartbeat);visit('closed');}});
 if(stageForm){
  let stageSubmitted=false, regularNavigation=false;
  stageForm.elements.visit_key.value=visitKey;
  const remind=action=>{stageForm.elements.after_stage.value=action||'';const notice=document.getElementById('lead-stage-notice');if(notice){notice.hidden=false;notice.classList.add('needs-attention');}stageForm.scrollIntoView({behavior:'smooth',block:'center'});stageForm.elements.stage.focus({preventScroll:true});};
  if(stageForm.dataset.stageCleared==='1')stageSubmitted=true;
  const guardUrl=location.href;
  const armGuard=()=>{if(history.state?.leadStageGuard)return;history.replaceState({...history.state,leadStageBase:true},'',guardUrl);history.pushState({leadStageGuard:true},'',guardUrl);};
  armGuard();
  window.addEventListener('pageshow',armGuard);
  window.addEventListener('popstate',()=>{if(stageSubmitted||regularNavigation)return;history.pushState({leadStageGuard:true},'',guardUrl);remind();});
  document.addEventListener('click',event=>{const link=event.target.closest('a[href]');if(!link||stageSubmitted)return;if(link.matches('[data-lead-stage-required], .sidebar a[href]')){event.preventDefault();remind(link.dataset.leadStageRequired);return;}const url=new URL(link.href,location.href);if(url.origin===location.origin&&url.pathname===location.pathname&&url.search===location.search&&url.hash)return;regularNavigation=true;setTimeout(()=>{regularNavigation=false;},250);},true);
  document.addEventListener('submit',event=>{if(event.target===stageForm||stageSubmitted)return;if(event.target.matches('[data-lead-stage-required], .sidebar form')){event.preventDefault();remind(event.target.dataset.leadStageRequired);return;}regularNavigation=true;setTimeout(()=>{regularNavigation=false;},250);},true);
  stageForm.addEventListener('submit',()=>{stageSubmitted=true;});
  window.addEventListener('beforeunload',event=>{if(stageSubmitted||regularNavigation||document.querySelector('[data-lead-call-dialog][open]'))return;event.preventDefault();event.returnValue='';});
 }
 const key='lead-view:'+profile.dataset.leadState;let restoring=false;const previousRestoration=history.scrollRestoration;history.scrollRestoration='manual';
 const save=()=>{if(restoring)return;try{const anchors=[...document.querySelectorAll('.work-card[id],section[id]')];const anchor=anchors.filter(el=>el.getBoundingClientRect().top<=100).at(-1);sessionStorage.setItem(key,JSON.stringify({time:Date.now(),y:scrollY,anchor:anchor?.id,offset:anchor?.getBoundingClientRect().top,open:[...document.querySelectorAll('details[id][open]')].map(el=>el.id),feed:document.querySelector('.lead-activity-feed')?.scrollTop||0}));}catch{}};
 const restore=()=>{try{const state=JSON.parse(sessionStorage.getItem(key)||'null');if(!state||Date.now()-state.time>1800000)return;restoring=true;state.open?.forEach(id=>{const el=document.getElementById(id);if(el?.tagName==='DETAILS')el.open=true;});const apply=()=>{const anchor=state.anchor&&document.getElementById(state.anchor);window.scrollTo({top:anchor?scrollY+anchor.getBoundingClientRect().top-state.offset:state.y,behavior:'instant'});const feed=document.querySelector('.lead-activity-feed');if(feed)feed.scrollTop=state.feed;restoring=false;};requestAnimationFrame(()=>requestAnimationFrame(apply));}catch{restoring=false;}};
 document.addEventListener('submit',save,true);document.addEventListener('click',event=>{if(event.target.closest('a'))save();},true);window.addEventListener('pagehide',()=>{save();history.scrollRestoration=previousRestoration;});window.addEventListener('pageshow',()=>{history.scrollRestoration='manual';restore();});restore();
 let pending=false;const refreshScore=async()=>{if(pending||document.hidden)return;pending=true;try{const response=await fetch(profile.dataset.scoreUrl,{headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)return;const result=await response.json();if(!Number.isInteger(result.score))return;document.querySelectorAll('[data-live-score]').forEach(el=>{el.textContent=result.score;el.setAttribute('aria-label',`Score ${result.score} out of 100`);});const label=document.querySelector('[data-live-score-label]');if(label)label.textContent=result.configured?`Contact score: ${result.score} / 100`:'Score weightage not configured';}catch{}finally{pending=false;}};
 window.addEventListener('storage',event=>{if(event.key==='lead-weights-updated')refreshScore();});window.addEventListener('focus',refreshScore);window.addEventListener('pageshow',refreshScore);document.addEventListener('visibilitychange',refreshScore);setInterval(refreshScore,10000);refreshScore();
});
document.addEventListener('DOMContentLoaded',()=>{
 const weights=document.getElementById('lead-weights');
 if(weights){
  const rows=[...weights.querySelectorAll('.weight-setting')];
  const value=row=>Number(row.querySelector('[data-weight-number]').value)||0;
  const update=()=>{const total=rows.reduce((sum,row)=>sum+value(row),0);document.getElementById('weight-total').textContent=total;document.getElementById('weight-remaining').textContent=Math.max(0,100-total);document.getElementById('weight-hint').textContent=total>100?'Reduce the total to 100 or less before saving.':total===0?'All fields are excluded. Lead scores will be 0.':'Each filled field earns its assigned points. '+(100-total)+' points remain.';rows.forEach(row=>{const available=Math.max(0,100-total+value(row));row.querySelector('[data-weight-number]').max=available;row.querySelector('[data-weight-slider]').max=available;});};
  rows.forEach(row=>{const number=row.querySelector('[data-weight-number]'),slider=row.querySelector('[data-weight-slider]');number.addEventListener('input',()=>{const available=Math.max(0,100-rows.reduce((sum,other)=>sum+(other===row?0:value(other)),0));if(number.value!==''&&Number(number.value)>available)number.value=available;slider.value=number.value||0;update();});slider.addEventListener('input',()=>{number.value=slider.value;update();});});update();
 }
 const form=document.getElementById('overview-compose');if(!form)return;
 const descriptions={Note:['New note','Save an internal note for your team.','Note details *','Write the important details…'], 'Call log':['Log a call','Record a call you have already made or received.','Call summary *','What was discussed? Add the outcome and next steps…'], 'Customer note':['New customer note','Record the customer’s requirements or feedback.','Customer requirements / feedback *','What did the customer ask for?…'], Email:['Compose email','This sends an actual email to the contact shown below.','Email message *','Write your email message…']};
 const select=kind=>{form.hidden=false;const copy=descriptions[kind]||descriptions.Note;const set=(id,text)=>{const el=document.getElementById(id);if(el)el.textContent=text;};set('overview-heading',copy[0]);set('overview-help',copy[1]);set('overview-body-text',copy[2]);set('overview-subject-label',kind==='Email'?'Subject *':'Title (optional)');form.elements.body.placeholder=copy[3];const prompt=document.getElementById('overview-prompt');if(prompt)prompt.hidden=true;form.elements.kind.value=kind;document.querySelectorAll('[data-compose-kind]').forEach(button=>{const active=button.dataset.composeKind===kind;button.classList.toggle('is-active',active);button.setAttribute('aria-pressed',String(active));});document.getElementById('overview-email-recipient').hidden=kind!=='Email';const callDirection=document.getElementById('overview-call-direction');callDirection.hidden=kind!=='Call log';callDirection.querySelectorAll('input').forEach(input=>{input.required=kind==='Call log';input.disabled=kind!=='Call log';});form.elements.subject.required=kind==='Email';document.getElementById('overview-submit').textContent=kind==='Email'?'Send email':'Save '+kind.toLowerCase();};
 document.querySelectorAll('[data-compose-kind]').forEach(button=>button.addEventListener('click',()=>select(button.dataset.composeKind)));
 if(form.elements.body.value)select(form.elements.kind.value);
 const compose=new URLSearchParams(location.search).get('compose');
 if(compose==='call-log'){
  select('Call log');
  const outgoing=form.querySelector('input[name="call_direction"][value="Outgoing"]');if(outgoing)outgoing.checked=true;
  const cleanUrl=new URL(location.href);cleanUrl.searchParams.delete('compose');history.replaceState(history.state,'',cleanUrl);
  setTimeout(()=>{form.scrollIntoView({behavior:'smooth',block:'center'});form.elements.body.focus({preventScroll:true});},50);
 }
 form.addEventListener('submit',()=>{document.getElementById('overview-submit').disabled=true;});
});
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('[data-delete-activity]').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Remove this activity from history? Sent emails will not be recalled.'))event.preventDefault();}));
 const form=document.getElementById('lead-weights');if(!form)return;
 const status=document.getElementById('weight-save-status');let timer,busy=false,dirty=false;
 const total=()=>[...form.querySelectorAll('[data-weight-number]')].reduce((sum,input)=>sum+(Number(input.value)||0),0);
 const save=async()=>{if(busy){dirty=true;return;}if(!form.checkValidity()||total()>100){status.textContent='Not saved: use whole numbers and keep the combined total at 100 or less.';return;}busy=true;dirty=false;status.textContent='Saving weightage…';try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok){status.textContent=Object.values(data.errors||{}).flat().join(' ')||'Could not save. Sign in again or refresh the settings.';return;}document.querySelectorAll('input[name="version"]').forEach(input=>input.value=data.version);status.textContent='Saved. Lead scores are updated using their filled contact fields.';try{localStorage.setItem('lead-weights-updated',String(Date.now()));}catch{}}catch{status.textContent='Not saved: connection failed. Click Save weightage to retry.';}finally{busy=false;if(dirty)save();}};
 form.addEventListener('input',()=>{dirty=true;clearTimeout(timer);status.textContent=total()<=100?'Unsaved changes — saving shortly…':'Not saved: the combined total must be 100 or less.';if(total()<=100)timer=setTimeout(save,700);});
 form.addEventListener('submit',event=>{event.preventDefault();clearTimeout(timer);save();});
});

document.addEventListener('DOMContentLoaded',()=>{
 const dialog=document.querySelector('[data-lead-call-dialog]');
 if(!dialog)return;
 const form=dialog.querySelector('[data-lead-call-form]');
 const outcome=form.elements.outcome;
 const details=form.elements.details;
 const notes=dialog.querySelector('[data-call-notes]');
 const detailsLabel=dialog.querySelector('[data-call-details-label]');
 const exitButton=dialog.querySelector('[data-call-exit]');
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
 let opening=false;
 const resetCall=()=>{
  outcome.value='';details.value='';details.required=false;details.setCustomValidity('');notes.hidden=true;exitButton.hidden=true;form.dataset.submitting='false';
  dialog.querySelectorAll('[data-call-connected], [data-call-unanswered]').forEach(button=>{
   button.classList.remove('primary');button.classList.add('secondary');button.setAttribute('aria-pressed','false');button.setAttribute('aria-expanded','false');
  });
 };
 const open=async()=>{
  if(opening)return;opening=true;
  try{
   const response=await fetch(dialog.dataset.callStartUrl,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf||'','Accept':'application/json'}});
   if(!response.ok)throw new Error('Could not start the call.');
   resetCall();
   form.elements.request_key.value=crypto.randomUUID();
   if(!dialog.open)dialog.showModal();
  }catch(error){alert(error.message);}finally{opening=false;}
 };
 document.querySelectorAll('[data-lead-call-open]').forEach(button=>button.addEventListener('click',open));
 dialog.querySelector('[data-lead-call-close]').addEventListener('click',()=>dialog.close());
 exitButton.addEventListener('click',()=>{if(outcome.value)form.requestSubmit();else dialog.close();});
 dialog.addEventListener('cancel',event=>{event.preventDefault();event.stopImmediatePropagation();dialog.close();});
 document.addEventListener('keydown',event=>{
  if(!dialog.open||event.key!=='Escape')return;
  event.preventDefault();event.stopImmediatePropagation();
  dialog.close();
 },true);
 const chooseOutcome=(value)=>{
  if(outcome.value===value&&!notes.hidden){
   outcome.value='';notes.hidden=true;details.required=false;details.setCustomValidity('');
   dialog.querySelectorAll('[data-call-connected], [data-call-unanswered]').forEach(button=>{
    button.classList.remove('primary');button.classList.add('secondary');button.setAttribute('aria-pressed','false');button.setAttribute('aria-expanded','false');
   });
   return;
  }
  outcome.value=value;notes.hidden=false;details.required=value==='Connected';exitButton.hidden=value!=='Unanswered';details.setCustomValidity('');
  detailsLabel.textContent=value==='Connected'?'Call details *':'Call notes (optional)';
  details.placeholder=value==='Connected'?'What was discussed? Add the outcome and next step.':'Why was the call unanswered? Add the retry or follow-up plan.';
  dialog.querySelectorAll('[data-call-connected], [data-call-unanswered]').forEach(button=>{
   const selected=button.hasAttribute(value==='Connected'?'data-call-connected':'data-call-unanswered');
   button.classList.toggle('primary',selected);button.classList.toggle('secondary',!selected);button.setAttribute('aria-pressed',String(selected));button.setAttribute('aria-expanded',String(selected));
  });
  dialog.querySelector('[data-call-note-help]').textContent=value==='Connected'?'Add call details, then submit to save the call.':'Notes are optional. Submit saves the unanswered call.';
 };
 dialog.querySelector('[data-call-connected]').addEventListener('click',()=>chooseOutcome('Connected'));
 dialog.querySelector('[data-call-unanswered]').addEventListener('click',()=>chooseOutcome('Unanswered'));
 form.addEventListener('submit',event=>{
  if(!outcome.value){event.preventDefault();form.dataset.submitting='false';return;}
  if(outcome.value==='Connected'&&!details.value.trim()){
   event.preventDefault();form.dataset.submitting='false';details.setCustomValidity('Enter call details before saving.');details.reportValidity();details.focus();return;
  }
  details.setCustomValidity('');
 });
 details.addEventListener('input',()=>details.setCustomValidity(''));
 if(outcome.value)chooseOutcome(outcome.value);
});

// Present each activity as a complete labelled record while keeping stored text unchanged.
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('.lead-timeline-row').forEach(row=>{
  const content=row.querySelector(':scope > div');
  if(!content)return;
  content.classList.add('lead-activity-content');
  content.querySelector(':scope > strong')?.classList.add('lead-activity-title');
  const smalls=[...content.querySelectorAll(':scope > small')];
  const metadata=smalls.find(element=>element.textContent.includes(' · ')&&/\d{2} \w{3} \d{4}/.test(element.textContent));
  if(metadata){
   const parts=metadata.textContent.replace(/\s+IST\s*$/,'').split(' · ').map(part=>part.trim()).filter(Boolean);
   const meta=document.createElement('div');meta.className='lead-activity-meta';
   const entries=parts.length>3?[[parts[0],'Direction'],[parts[2],'Recorded by'],[parts[3],'Date & time']]:[[parts[1],'Recorded by'],[parts[2],'Date & time']];
   entries.forEach(([part,name],index)=>{const item=document.createElement(index===entries.length-1?'time':'span');const label=document.createElement('b');label.textContent=name+': ';item.append(label,part);meta.append(item);});
   metadata.replaceWith(meta);
  }
  const outcome=row.querySelector('.call-direction-icon')?smalls.find(element=>element.isConnected&&element!==metadata):null;
  if(outcome){const box=document.createElement('div');box.className='lead-activity-outcome';const label=document.createElement('span');label.textContent='Outcome';const value=document.createElement('strong');value.textContent=outcome.textContent.trim();box.append(label,value);outcome.replaceWith(box);}
  const details=content.querySelector(':scope > .lead-pre');
  if(details){const wrapper=document.createElement('div');wrapper.className='lead-activity-details';const label=document.createElement('span');label.textContent='Details';details.before(wrapper);wrapper.append(label,details);}
 });
});

document.addEventListener('DOMContentLoaded',()=>{
 const picker=document.getElementById('overview-call-direction');
 if(picker&&!picker.querySelector('input[value="Missed call"]')){
  const label=document.createElement('label');
  label.innerHTML='<input type="radio" name="call_direction" value="Missed call" required><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16.4v2.8a2 2 0 0 1-2.2 2A18.9 18.9 0 0 1 3 5.2 2 2 0 0 1 5 3h2.8a2 2 0 0 1 2 1.7l.5 2.7a2 2 0 0 1-.6 1.8L8 10.9a15 15 0 0 0 5.1 5.1l1.7-1.7a2 2 0 0 1 1.8-.6l2.7.5a2 2 0 0 1 1.7 2Z"/><path d="m14 3 6 6m0-6-6 6"/></svg>Missed Call</span>';
  picker.append(label);
 }
 document.querySelectorAll('.activity-actions select[name="call_direction"]').forEach(select=>{
  if(!select.querySelector('option[value="Missed call"]'))select.add(new Option('Missed Call','Missed call'));
  if(/^Missed call(?:$|: )/i.test(select.closest('article')?.querySelector('.lead-activity-title')?.textContent||'')){
   select.value='Missed call';
   const subject=select.closest('form')?.querySelector('input[name="subject"]');
   if(subject)subject.value=subject.value.replace(/^Missed call(?:: )?/i,'');
  }
 });
 const filters=document.querySelector('.call-log-filters');
 if(filters&&!filters.querySelector('[data-missed-call-filter]')){
  const current=new URL(location.href),url=new URL(location.href);url.searchParams.set('activity','Call log');url.searchParams.set('call_filter','missed');url.hash='lead-history';
  const link=document.createElement('a');link.className='button secondary'+(current.searchParams.get('call_filter')==='missed'?' is-active':'');link.href=url.href;link.dataset.missedCallFilter='';link.textContent='Missed Calls';filters.append(link);
 }
});

document.addEventListener('DOMContentLoaded',()=>{
 const back=document.querySelector('[data-lead-back-history="true"]');
 if(!back)return;
 back.addEventListener('click',event=>{
  if(history.length<2)return;
  event.preventDefault();
  history.back();
 });
});
