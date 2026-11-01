<x-app-layout :title="$type.'s'">
<link rel="stylesheet" href="{{ asset('css/productivity.css').'?v='.filemtime(public_path('css/productivity.css')) }}">
<main class="productivity-page" data-productivity>
 <nav class="productivity-module-nav" aria-label="Productivity modules">
  <a href="{{ route('tasks.index') }}"><span>▣</span> Task</a>
  <a class="{{ $module === 'reminders' ? 'is-active' : '' }}" href="{{ route('productivity.index','reminders') }}"><span>◷</span> Reminder</a>
  <a class="{{ $module === 'meetings' ? 'is-active' : '' }}" href="{{ route('productivity.index','meetings') }}"><span>♟</span> Meeting</a>
  <a class="{{ $module === 'to-dos' ? 'is-active' : '' }}" href="{{ route('productivity.index','to-dos') }}"><span>☷</span> To-Do</a>
  <a class="{{ $module === 'notes' ? 'is-active' : '' }}" href="{{ route('productivity.index','notes') }}"><span>▮</span> Note</a>
 </nav>
 <section class="productivity-card productivity-board-page" data-productivity-board>
  <header class="productivity-head">
   <div><p class="eyebrow">Workspace</p><h1>{{ $type === 'To-Do' ? 'To-Dos' : $type.'s' }}</h1><p>Create, assign and track {{ strtolower($type) }} records.</p></div>
   <div class="productivity-tools"><button class="button primary" type="button" data-productivity-open>+ Add {{ $type }}</button><button class="button secondary" type="button" data-productivity-filter-toggle>☷ Filter</button><div class="productivity-view-buttons"><button type="button" class="is-active" data-productivity-view="board" title="Pipeline view">▣</button><button type="button" data-productivity-view="list" title="List view">☷</button></div></div>
  </header>
  <form class="productivity-search" data-productivity-filter><input name="q" value="{{ $q }}" placeholder="Search {{ strtolower($type) }}"><button class="button primary">Apply filter</button>@if($q)<a class="button ghost" href="{{ route('productivity.index',$module) }}">Clear</a>@endif</form>
  <div class="productivity-board" data-productivity-board-view>
   @foreach($statuses as $status)
    @php($column = $items->getCollection()->where('status', $status->name))
    <section class="productivity-column" style="--productivity-status:{{ $status->color }}"><header><strong>{{ $status->name }}</strong><span>{{ $column->count() }}</span></header><div class="productivity-column-body" data-productivity-dropzone data-status="{{ $status->name }}">
    @foreach($column as $item)
     <article class="productivity-work-card" draggable="true" data-productivity-card data-status-url="{{ route('productivity.status',[$module,$item]) }}" data-current-status="{{ $item->status }}"><strong>{{ $item->title }}</strong><small>{{ $item->priority ? $item->priority.' priority' : 'No priority' }}</small><dl><div><dt>TO:</dt><dd>{{ $item->assignees->pluck('name')->join(', ') ?: ($item->assignee?->name ?? 'Unassigned') }}</dd></div><div><dt>DUE:</dt><dd>{{ $item->scheduled_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? '—' }}</dd></div></dl><footer><form method="POST" action="{{ route('productivity.destroy',$item) }}" onsubmit="return confirm('Delete this {{ strtolower($type) }}?')">@csrf @method('DELETE')<button class="icon-button danger" title="Delete">×</button></form></footer></article>
    @endforeach
    </div></section>
   @endforeach
  </div>
  <div class="table-scroll productivity-list-view" data-productivity-list-view hidden><table>
   @if($type === 'Note')
    <thead><tr><th>No.</th><th>Notes</th><th>Date &amp; time</th><th>Created by</th><th>Assigned to</th><th>Actions</th></tr></thead>
   @elseif($type === 'To-Do')
    <thead><tr><th>No.</th><th>To-Do</th><th>Date &amp; time</th><th>Priority</th><th>Assigned to</th><th>Created by</th><th>Actions</th></tr></thead>
   @else
    <thead><tr><th>No.</th><th>Actions</th><th>Message</th><th>Date</th><th>Time</th><th>Type</th><th>Created at</th><th>Created by</th><th>Assigned to</th></tr></thead>
   @endif
   <tbody>
   @forelse($items as $item)
    @php($assignedNames = $item->assignees->pluck('name')->join(', ') ?: ($item->assignee?->name ?? 'Unassigned'))
    @if($type === 'Note')
     <tr><td>{{ $items->firstItem()+$loop->index }}</td><td>{{ Str::limit(strip_tags($item->message ?: $item->title), 140) }}</td><td>{{ $item->scheduled_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? '—' }}</td><td>{{ $item->creator?->name ?? '—' }}</td><td>{{ $assignedNames }}</td><td><form method="POST" action="{{ route('productivity.destroy',$item) }}" onsubmit="return confirm('Delete this note?')">@csrf @method('DELETE')<button class="icon-button danger" title="Delete">×</button></form></td></tr>
    @elseif($type === 'To-Do')
     <tr><td>{{ $items->firstItem()+$loop->index }}</td><td><strong>{{ $item->title }}</strong></td><td>{{ $item->scheduled_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? '—' }}</td><td>{{ $item->priority ?? '—' }}</td><td>{{ $assignedNames }}</td><td>{{ $item->creator?->name ?? '—' }}</td><td><form method="POST" action="{{ route('productivity.destroy',$item) }}" onsubmit="return confirm('Delete this to-do?')">@csrf @method('DELETE')<button class="icon-button danger" title="Delete">×</button></form></td></tr>
    @else
     <tr><td>{{ $items->firstItem()+$loop->index }}</td><td><form method="POST" action="{{ route('productivity.destroy',$item) }}" onsubmit="return confirm('Delete this {{ strtolower($type) }}?')">@csrf @method('DELETE')<button class="icon-button danger" title="Delete">×</button></form></td><td>{{ Str::limit(strip_tags($item->message ?: $item->title),90) }}</td><td>{{ $item->scheduled_at?->timezone('Asia/Kolkata')->format('d M Y') ?? '—' }}</td><td>{{ $item->scheduled_at?->timezone('Asia/Kolkata')->format('h:i A') ?? '—' }}</td><td>{{ $item->template_type ?? '—' }}</td><td>{{ $item->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? '—' }}</td><td>{{ $item->creator?->name ?? '—' }}</td><td>{{ $assignedNames }}</td></tr>
    @endif
   @empty
    <tr><td colspan="9" class="empty-state">There are no records to display.</td></tr>
   @endforelse
   </tbody></table></div>{{ $items->links() }}
 </section>
</main>
<dialog class="productivity-dialog" data-productivity-dialog><form method="POST" enctype="multipart/form-data" action="{{ route('productivity.store',$module) }}">@csrf
 <header><h2>Add {{ $type }}</h2><button type="button" class="icon-button" data-productivity-close>×</button></header>
 @if($type !== 'Note')
 <div class="recurrence">@foreach(['Once','Daily','Weekly','Monthly','Quarterly','Half-Yearly','Yearly'] as $repeat)<label><input type="radio" name="recurrence" value="{{ $repeat }}" @checked($repeat==='Once')> {{ $repeat }}</label>@endforeach</div>
 <div class="form-grid"><label>Date &amp; time <input type="datetime-local" name="scheduled_at" required value="{{ now('Asia/Kolkata')->addHour()->format('Y-m-d\TH:i') }}"></label><label>Assign to users <select name="assigned_to[]" multiple size="4">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><small>Hold Ctrl / Cmd to select multiple users.</small></label>
 @if(in_array($type,['Reminder','Meeting']))<label>Template type <span class="radio-line"><input type="radio" name="template_type" value="WhatsApp" checked> WhatsApp <input type="radio" name="template_type" value="Meta"> Meta</span></label><label class="toggle-label">Send instant <input type="checkbox" name="send_instant" value="1"></label><label class="wide">{{ $type }} template <input name="template_name" placeholder="Select or enter template"></label>@else<label>Priority <select name="priority"><option>High</option><option>Normal</option><option>Low</option></select></label><label class="toggle-label">Carry forward <input type="checkbox" name="carry_forward" value="1"></label>@endif
 <label class="wide">{{ $type === 'To-Do' ? 'To-Do' : 'Title' }} <input name="title" required placeholder="Enter {{ strtolower($type) }} title"></label>@if($type !== 'To-Do')<label class="wide">Message <textarea name="message" rows="4" placeholder="Enter {{ strtolower($type) }} details"></textarea></label>@else<input type="hidden" name="message" value="">@endif
 @if(in_array($type,['Reminder','Meeting']))<fieldset class="wide automation"><legend>Automation</legend>
  <div class="automation-row" data-automation-row>
   <label class="automation-switch"><span>Email automation</span><input type="checkbox" name="email_automation" value="1" data-automation-toggle aria-controls="email-automation-fields"></label>
   <div class="automation-fields" id="email-automation-fields" data-automation-fields hidden><div class="automation-inputs" data-automation-inputs><label>Customer Email: <b>*</b><span class="automation-input-row"><input type="email" name="customer_emails[]" placeholder="Email" disabled></span></label></div><button type="button" class="automation-action" data-automation-add aria-label="Add another customer email">+</button><button type="button" class="automation-action is-outline" data-automation-preview aria-label="Preview customer email"><x-icon name="eye" size="18"/></button></div>
  </div>
  <div class="automation-row" data-automation-row>
   <label class="automation-switch"><span>WhatsApp automation</span><input type="checkbox" name="whatsapp_automation" value="1" data-automation-toggle aria-controls="whatsapp-automation-fields"></label>
   <div class="automation-fields" id="whatsapp-automation-fields" data-automation-fields hidden><div class="automation-inputs" data-automation-inputs><label>Customer Mobile <span class="automation-input-row mobile-input"><b>🇮🇳</b><em>+91</em><input name="customer_mobiles[]" placeholder="Mobile number" inputmode="tel" disabled></span></label></div><button type="button" class="automation-action" data-automation-add aria-label="Add another customer mobile">+</button><button type="button" class="automation-action is-outline" data-automation-preview aria-label="Preview customer mobile"><x-icon name="eye" size="18"/></button></div>
  </div>
 </fieldset>@else<label class="wide">Attachment (optional)<span class="upload-box">Drop file here or click to upload<input type="file" name="attachment"></span><small>Images, videos, PDF, DOC, Excel, PPT or text · max 5 MB</small></label>@endif</div>
 @else
 <div class="note-fields"><label>Date &amp; time <input type="datetime-local" name="scheduled_at" required value="{{ now('Asia/Kolkata')->format('Y-m-d\\TH:i') }}"></label><label>Assign to users <select name="assigned_to[]" multiple size="4">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><small>Hold Ctrl / Cmd to select multiple users.</small></label><label class="note-field">Notes <textarea name="title" required rows="6" placeholder="Enter notes"></textarea><input type="hidden" name="message" value=""></label></div>
 @endif
 <footer><button type="button" class="button secondary" data-productivity-close>Cancel</button><button class="button primary" type="submit">Submit</button></footer>
</form></dialog>
<script>document.addEventListener('DOMContentLoaded',()=>{const d=document.querySelector('[data-productivity-dialog]');document.querySelector('[data-productivity-open]')?.addEventListener('click',()=>d.showModal());document.querySelectorAll('[data-productivity-close]').forEach(b=>b.addEventListener('click',()=>d.close()));d?.addEventListener('click',e=>{if(e.target===d)d.close()});d?.querySelectorAll('[data-automation-row]').forEach(row=>{const toggle=row.querySelector('[data-automation-toggle]'),fields=row.querySelector('[data-automation-fields]'),inputsBox=fields?.querySelector('[data-automation-inputs]');const inputs=()=>[...inputsBox.querySelectorAll('input')];const sync=()=>{const open=toggle.checked;fields.hidden=!open;inputs().forEach((input,index)=>{input.disabled=!open;input.required=open&&index===0;});};toggle.addEventListener('change',sync);row.querySelector('[data-automation-add]')?.addEventListener('click',()=>{if(inputs().length>=5)return;const first=inputsBox.querySelector('label'),clone=first.cloneNode(true),input=clone.querySelector('input'),fieldRow=clone.querySelector('.automation-input-row');clone.childNodes.forEach(node=>{if(node.nodeType===Node.TEXT_NODE)node.textContent='';});input.value='';input.required=false;const close=document.createElement('button');close.type='button';close.className='automation-remove';close.setAttribute('aria-label','Remove this field');close.textContent='×';close.addEventListener('click',()=>clone.remove());fieldRow.append(close);inputsBox.append(clone);input.focus();});row.querySelector('[data-automation-preview]')?.addEventListener('click',()=>{const values=inputs().map(input=>input.value.trim()).filter(Boolean);if(!values.length){inputs()[0].focus();inputs()[0].reportValidity();return;}alert(values.join('\n'));});sync();});});</script>
<script>document.addEventListener('DOMContentLoaded',()=>{const root=document.querySelector('[data-productivity-board]');if(!root)return;const filter=root.querySelector('[data-productivity-filter]'),board=root.querySelector('[data-productivity-board-view]'),list=root.querySelector('[data-productivity-list-view]');root.querySelectorAll('[data-productivity-filter-toggle]').forEach(button=>button.addEventListener('click',()=>filter?.classList.toggle('is-open')));root.querySelectorAll('[data-productivity-view]').forEach(button=>button.addEventListener('click',()=>{const boardMode=button.dataset.productivityView==='board';board.hidden=!boardMode;list.hidden=boardMode;root.querySelectorAll('[data-productivity-view]').forEach(item=>item.classList.toggle('is-active',item===button));}));root.querySelectorAll('[data-productivity-status]').forEach(select=>select.addEventListener('change',()=>select.form.requestSubmit()));let card=null;root.querySelectorAll('[data-productivity-card]').forEach(item=>{item.addEventListener('dragstart',()=>{card=item;item.classList.add('is-dragging');});item.addEventListener('dragend',()=>{item.classList.remove('is-dragging');card=null;});});root.querySelectorAll('[data-productivity-dropzone]').forEach(zone=>{zone.addEventListener('dragover',event=>{event.preventDefault();zone.classList.add('is-over');});zone.addEventListener('dragleave',()=>zone.classList.remove('is-over'));zone.addEventListener('drop',async event=>{event.preventDefault();zone.classList.remove('is-over');if(!card||card.dataset.currentStatus===zone.dataset.status)return;const response=await fetch(card.dataset.statusUrl,{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify({status:zone.dataset.status})});if(response.ok)window.location.reload();else alert('Could not update status.');});});});</script>
</x-app-layout>
