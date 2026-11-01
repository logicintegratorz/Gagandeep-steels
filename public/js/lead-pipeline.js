document.addEventListener('DOMContentLoaded',()=>{
 const token=document.querySelector('meta[name="csrf-token"]')?.content;let dragged=null;
 document.querySelectorAll('[data-pipeline-card][draggable="true"]').forEach(card=>{
  card.addEventListener('dragstart',event=>{dragged=card;card.classList.add('is-dragging');event.dataTransfer.effectAllowed='move';});
  card.addEventListener('dragend',()=>{card.classList.remove('is-dragging');document.querySelectorAll('.is-drop-target').forEach(node=>node.classList.remove('is-drop-target'));dragged=null;});
 });
 document.querySelectorAll('[data-pipeline-stage]').forEach(column=>{
  column.addEventListener('dragover',event=>{if(!dragged)return;event.preventDefault();event.dataTransfer.dropEffect='move';column.classList.add('is-drop-target');});
  column.addEventListener('dragleave',event=>{if(!column.contains(event.relatedTarget))column.classList.remove('is-drop-target');});
  column.addEventListener('drop',async event=>{event.preventDefault();column.classList.remove('is-drop-target');if(!dragged||column.dataset.pipelineStage===dragged.dataset.currentStage)return;column.classList.add('is-saving');try{const response=await fetch(dragged.dataset.stageUrl,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({stage:column.dataset.pipelineStage,version:Number(dragged.dataset.version)})});if(!response.ok){const data=await response.json().catch(()=>({}));throw new Error(Object.values(data.errors||{}).flat()[0]||data.message||'Stage could not be changed.');}location.reload();}catch(error){column.classList.remove('is-saving');alert(error.message);}});
 });
});
