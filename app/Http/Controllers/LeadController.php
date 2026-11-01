<?php
namespace App\Http\Controllers;
use App\Models\{CalendarAssignment,JobRole,Lead,LeadWorkItem,User,CrmNotification,TaskStatus};
use App\Support\LeadIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
final class LeadController {
 private function access(Request $r,Lead $lead): void {abort_unless(Lead::visibleTo($r->user())->whereKey($lead->id)->exists(),404);}
 private function owners(){return User::with('role')->where('active',true)->orderBy('name')->get()->filter(fn($u)=>$u->allows('leads.view'));}
 private function query(Request $r){
  $r->validate(['q'=>'nullable|string|max:150','stage'=>['nullable',Rule::in(Lead::STAGES)],'owner'=>'nullable|integer','priority'=>['nullable',Rule::in(['Low','Normal','High'])],'job_role'=>['nullable',Rule::exists('job_roles','name')]]);
  $q=Lead::visibleTo($r->user())->with('owner')->withCount('workItems');
  if($r->filled('q')) {$term='%'.str_replace(['!','%','_'],['!!','!%','!_'],trim($r->q)).'%';$q->where(fn($q)=>$q->whereRaw("name LIKE ? ESCAPE '!'",[$term])->orWhereRaw("company LIKE ? ESCAPE '!'",[$term])->orWhereRaw("phone LIKE ? ESCAPE '!'",[$term])->orWhereRaw("email LIKE ? ESCAPE '!'",[$term]));}
  foreach(['stage','priority'] as $field) if($r->filled($field)) $q->where($field,$r->input($field));
  if($r->filled('job_role'))$q->where('job_role',$r->input('job_role'));
  if($r->filled('owner')) $q->where('owner_id',$r->integer('owner'));
  return $q;
 }
 public function index(Request $r){
  $r->validate(['view'=>'nullable|in:extended','contacts_filter'=>'nullable|in:touched']);
  $isExtended=$r->input('view')==='extended';
  $query=$this->query($r)->latest();
  $totalLeadCount=(clone $query)->count();
  $untouchedLeadCount=(clone $query)->untouched()->count();
  if($isExtended && $r->input('contacts_filter')==='touched'){
   $query->where(fn($q)=>$q->whereNotNull('owner_id')->orWhereNotNull('contact_saved_at')->orWhereHas('visits'));
  }elseif(!$isExtended && $r->user()->allows('leads.view_all')){
   $query->where(fn($q)=>$q->whereNotNull('owner_id')->orWhereNotNull('contact_saved_at'));
  }elseif(!$isExtended){
   $query->where(fn($q)=>$q->where('owner_id',$r->user()->id)->orWhere(fn($saved)=>$saved->whereNotNull('contact_saved_at')->where('created_by',$r->user()->id)));
  }
  if($r->user()->isAdmin())$query->with('latestVisit.user','latestTransfer');
  return view('leads.index',['leads'=>$query->paginate(20)->withQueryString(),'owners'=>$this->owners(),'jobRoles'=>JobRole::orderBy('name')->pluck('name'),'totalLeadCount'=>$totalLeadCount,'untouchedLeadCount'=>$untouchedLeadCount,'touchedLeadCount'=>$totalLeadCount-$untouchedLeadCount]);
 }
 public function pipeline(Request $r){
  $base=$this->query($r);$columns=[];
  foreach(Lead::STAGES as $index=>$stage){
   $pageName='stage_page_'.$index;
   $r->validate([$pageName=>'nullable|integer|min:1|max:100000']);
   $columns[$stage]=(clone $base)->where('stage',$stage)->latest('id')->paginate(10,['*'],$pageName)->withQueryString();
  }
  return view('leads.pipeline',['columns'=>$columns,'owners'=>$this->owners(),'jobRoles'=>JobRole::orderBy('name')->pluck('name')]);
 }
 public function create(Request $r){$data=$r->validate(['phone'=>'nullable|string|max:60']);return view('leads.form',['lead'=>new Lead(['stage'=>'New','priority'=>'Normal','phone'=>$data['phone']??null]),'owners'=>$this->owners(),'jobRoles'=>JobRole::orderBy('name')->pluck('name')]);}
 public function edit(Request $r,Lead $lead){$this->access($r,$lead);return view('leads.form',['lead'=>$lead,'owners'=>$this->owners(),'jobRoles'=>JobRole::orderBy('name')->pluck('name')]);}
 public function show(Request $r,Lead $lead){
  $this->access($r,$lead);
  if($lead->owner_id===null && $r->user()->allows('leads.view')){
   DB::transaction(function()use($r,$lead){
    $item=Lead::lockForUpdate()->findOrFail($lead->id);
    if($item->owner_id!==null || $item->contact_saved_at!==null || $item->visits()->exists())return;
    $item->owner_id=$r->user()->id;$item->claimed_by=$r->user()->id;$item->claimed_at=now();$item->version++;$item->save();
    $item->activities()->create(['actor'=>$r->user()->name,'actor_id'=>$r->user()->id,'action'=>'Lead assigned','kind'=>'System','details'=>'Unassigned → '.$r->user()->name.' (claimed on first open)']);
    CalendarAssignment::create(['actor_id'=>$r->user()->id,'recipient_id'=>$r->user()->id,'lead_id'=>$item->id,'kind'=>'Lead','title'=>'Lead assigned: '.$item->name,'detail'=>'Unassigned → '.$r->user()->name.' (claimed on first open)','link'=>route('leads.show',$item),'assigned_at'=>now()]);
   });
   $lead->refresh();
  }
  $this->access($r,$lead);
  if(!$r->user()->isAdmin()){
   $view=$lead->activities()->firstOrNew(['action'=>'Lead viewed','details'=>'user:'.$r->user()->id]);
   $view->actor=$r->user()->name;$view->actor_id=$r->user()->id;$view->kind='System';$view->updated_at=now();$view->save();
  }
  $r->validate(['activity'=>'nullable|in:Note,Call log,Customer note,Email,Task,System','call_filter'=>'nullable|in:incoming,outgoing,missed,message-sent,message-received']);
  $activity=$lead->activities()->where('action','!=','Lead viewed');
  if($r->filled('activity')) {if($r->activity==='Task')$activity->where(fn($q)=>$q->where('action','like','Task %')->orWhere('action','like','Follow-up %')->orWhere('action','like','Reminder %'));else $activity->where('kind',$r->activity);}
  if($r->activity==='Call log' && $r->filled('call_filter')){
   $prefix=['incoming'=>'Incoming call%','outgoing'=>'Outgoing call%','missed'=>'Missed call%','message-sent'=>'Message sent%','message-received'=>'Message received%'][$r->call_filter];
   $activity->where('action','like',$prefix);
  }
  $settings=\App\Models\LeadSetting::findOrFail(1);
  return view('leads.show',['lead'=>$lead->load('owner','creator','repeatMatches.previous'),'activities'=>$activity->latest('id')->paginate(15,['*'],'activity_page')->withQueryString(),'items'=>$lead->workItems()->visibleTo($r->user())->with('lead','assignee')->orderBy('status')->orderBy('due_at')->paginate(10,['*'],'work_page')->withQueryString(),'owners'=>$this->owners(),'jobRoles'=>JobRole::orderBy('name')->pluck('name'),'score'=>$settings->score($lead),'scoreWeights'=>$settings->weights ?? [],'scoreConfigured'=>filled($settings->weights),'emailReady'=>$settings->emailReady(),'staffViews'=>$r->user()->isAdmin()?$lead->visits()->with('user')->latest('id')->limit(20)->get():collect(),'contactLists'=>\App\Models\LeadList::when(!$r->user()->isAdmin(),fn($q)=>$q->where('created_by',$r->user()->id))->withExists(['members as contains_current_lead'=>fn($q)=>$q->whereKey($lead->id)])->orderBy('name')->get(),'leadGroups'=>\App\Models\LeadGroup::where('created_by',$r->user()->id)->withExists(['leads as contains_current_lead'=>fn($q)=>$q->whereKey($lead->id)])->orderBy('name')->get()]);
 }
 public function visit(Request $r,Lead $lead){
  $this->access($r,$lead);
  $data=$r->validate(['session_key'=>'required|uuid','event'=>['required',Rule::in(['opened','heartbeat','closed'])]]);
  $visit=$lead->visits()->firstOrCreate(['session_key'=>$data['session_key']],['user_id'=>$r->user()->id,'opened_at'=>now(),'last_seen_at'=>now()]);
  abort_unless($visit->user_id===$r->user()->id,403);
  if(!$visit->stage_updated_at){
   if($data['event']==='closed')$visit->closed_at=now();
   elseif(!$visit->closed_at)$visit->last_seen_at=now();
   $visit->save();
  }
  return response()->noContent();
 }
 public function score(Request $r,Lead $lead){$this->access($r,$lead);$settings=\App\Models\LeadSetting::findOrFail(1);return response()->json(['score'=>$settings->score($lead),'configured'=>filled($settings->weights)])->header('Cache-Control','no-store');}
 public function store(Request $r){return $this->save($r);}
 public function update(Request $r,Lead $lead){$this->access($r,$lead);return $this->save($r,$lead);}
 public function destroy(Request $r,Lead $lead){
  $this->access($r,$lead);
  $data=$r->validate(['version'=>'required|integer']);
  DB::transaction(function()use($r,$lead,$data){
   $item=Lead::lockForUpdate()->findOrFail($lead->id);
   $this->access($r,$item);
   $this->version($item,$data['version']);
   $this->activity($r,$item,'Lead deleted','Lead archived; previous activities and work remain available to administrators.');
   $item->delete();
  });
  return redirect()->route('leads.index')->with('success','Lead deleted.');
 }
 public function deleteUntouched(Request $r){
  abort_unless($r->user()->allows('leads.delete'),403);
  $dates=$r->validate(['from'=>'required|date_format:Y-m-d','to'=>'required|date_format:Y-m-d|after_or_equal:from']);
  $from=\Carbon\CarbonImmutable::parse($dates['from'],'Asia/Kolkata')->startOfDay();
  $to=\Carbon\CarbonImmutable::parse($dates['to'],'Asia/Kolkata')->endOfDay();
  $count=0;
  DB::transaction(function()use($r,$from,$to,&$count){
   Lead::untouched()->whereNull('untouched_archived_at')->whereBetween('created_at',[$from,$to])->orderBy('id')->chunkById(100,function($leads)use($r,&$count){
    foreach($leads as $lead){
     $locked=Lead::lockForUpdate()->find($lead->id);
     if(!$locked || !Lead::untouched()->whereNull('untouched_archived_at')->whereKey($locked->id)->exists())continue;
     $locked->untouched_archived_at=now();$locked->version++;$locked->save();
     $this->activity($r,$locked,'Untouched lead archived','Archived while remaining visible in the homepage pool.');
     $count++;
    }
   });
  });
  return redirect()->route('leads.archived')->with('success',$count.' untouched '.($count===1?'lead':'leads').' added to the archive.');
 }
 public function archivedUntouched(Request $r){
  abort_unless($r->user()->allows('leads.delete'),403);
  $filters=$r->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d|after_or_equal:from','name'=>'nullable|string|max:255','company'=>'nullable|string|max:255','phone'=>'nullable|string|max:60','source'=>'nullable|string|max:100']);
  $query=Lead::visibleTo($r->user())->where(function($leads){$leads->whereNotNull('untouched_archived_at')->orWhere(fn($untouched)=>$untouched->untouched());})->with('owner')->latest('untouched_archived_at');
  if(filled($filters['from'] ?? null))$query->whereDate('untouched_archived_at','>=',$filters['from']);
  if(filled($filters['to'] ?? null))$query->whereDate('untouched_archived_at','<=',$filters['to']);
  foreach(['name','company','phone','source'] as $field)if(filled($filters[$field] ?? null))$query->where($field,'like','%'.$filters[$field].'%');
  return view('leads.archived-untouched',['leads'=>$query->paginate(20)->withQueryString(),'filters'=>collect($filters)->filter(fn($value)=>filled($value))->all()]);
 }
 public function showArchivedUntouched(Request $r,int $leadId){
  abort_unless($r->user()->allows('leads.delete'),403);
  $lead=Lead::visibleTo($r->user())->where(function($leads){$leads->whereNotNull('untouched_archived_at')->orWhere(fn($untouched)=>$untouched->untouched());})->with(['owner','creator','activities'=>fn($activities)=>$activities->latest('id')])->findOrFail($leadId);
  $filters=$r->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d|after_or_equal:from','name'=>'nullable|string|max:255','company'=>'nullable|string|max:255','phone'=>'nullable|string|max:60','source'=>'nullable|string|max:100']);
  return view('leads.archived-untouched-show',['lead'=>$lead,'returnTo'=>route('leads.archived',collect($filters)->filter(fn($value)=>filled($value))->all())]);
 }
 private function save(Request $r,?Lead $lead=null){
  $data=$r->validate(['name'=>'required|string|max:255','company'=>'nullable|string|max:255','phone'=>'nullable|string|max:60','alt_phone'=>'nullable|string|max:60','email'=>'nullable|email|max:255','website'=>'nullable|url:http,https|max:500','whatsapp'=>'nullable|string|max:60','location'=>'nullable|string|max:255','source'=>'nullable|string|max:100','category'=>'nullable|string|max:100','job_role'=>[$lead?'sometimes':'required','required',Rule::exists('job_roles','name')],'stage'=>['required',Rule::in(Lead::STAGES)],'priority'=>['required',Rule::in(['Low','Normal','High'])],'value'=>'nullable|numeric|min:0|max:9999999999999.99','notes'=>'nullable|string|max:20000','version'=>$lead?'required|integer':'nullable|integer']);
  $saved=DB::transaction(function()use($r,$lead,$data){
   $item=$lead?Lead::lockForUpdate()->findOrFail($lead->id):new Lead;
   if($lead){$this->access($r,$item);$this->version($item,$data['version']);}
   $before=$item->getAttributes();unset($data['version']);$item->fill($data);
   [$item->identity_phone,$item->identity_profile]=LeadIdentity::keys($item->name,$item->company,$item->phone,$item->location);
   if(!$lead){$item->contact_saved_at=$r->user()->isAdmin()?now():null;$item->source=$item->source ?: 'Manual';$item->created_by=$r->user()->id;$item->owner_id=$r->user()->id;}
   $item->version=($item->version??0)+1;$item->save();
   if(!$lead)LeadIdentity::attachPrevious($item);
   $changes=[];foreach($data as $key=>$value) if($lead && (string)($before[$key]??'')!==(string)$value) $changes[]=ucfirst($key).': '.($before[$key]??'—').' → '.($value??'—');
   $this->activity($r,$item,$lead?'Lead updated':'Lead created',implode("\n",$changes));return $item;
  });
  return $lead
   ? redirect()->route('leads.index')->with('success','Lead updated.')
   : redirect()->route('leads.show',$saved)->with('success','Lead created.');
 }
 public function assign(Request $r,Lead $lead){
  $this->access($r,$lead);$this->transferAccess($r,$lead);$data=$r->validate(['owner_id'=>'required|integer|exists:users,id','version'=>'required|integer']);
  $owner=User::findOrFail($data['owner_id']);if(!$owner->allows('leads.view')) throw ValidationException::withMessages(['owner_id'=>'Choose an active user with lead access.']);
  DB::transaction(function()use($r,$lead,$data,$owner){$item=Lead::lockForUpdate()->findOrFail($lead->id);$this->access($r,$item);$this->transferAccess($r,$item);$this->version($item,$data['version']);if($item->owner_id===$owner->id)return;
   $previous=$item->owner?->name??'Unassigned';$item->owner_id=$owner->id;$item->version++;$item->save();

   $this->activity($r,$item,'Lead assigned',$previous.' → '.$owner->name);
   CrmNotification::create(['user_id'=>$owner->id,'title'=>'Lead assigned: '.$item->name,'message'=>$r->user()->name.' assigned this lead to you. Open Leads to follow up.','permission'=>'leads.view']);
   CalendarAssignment::create(['actor_id'=>$r->user()->id,'recipient_id'=>$owner->id,'lead_id'=>$item->id,'kind'=>'Lead','title'=>'Lead assigned: '.$item->name,'detail'=>$previous.' → '.$owner->name,'link'=>route('leads.show',$item),'assigned_at'=>now()]);
  });
  return (Lead::visibleTo($r->user())->whereKey($lead->id)->exists()?redirect()->route('leads.show',$lead):redirect()->route('leads.index'))->with('success','Lead transferred to '.$owner->name.'.');
 }
 public function bulkTransfer(Request $r){
  $data=$r->validate(['lead_ids'=>'required|array|min:1|max:50','lead_ids.*'=>'required|integer|distinct','owner_id'=>'required|integer|exists:users,id','versions'=>'required|array','versions.*'=>'integer|min:1']);
  $owner=User::findOrFail($data['owner_id']);
  if(!$owner->allows('leads.view'))throw ValidationException::withMessages(['owner_id'=>'Choose an active user with lead access.']);
  $ids=array_map('intval',$data['lead_ids']);
  $count=DB::transaction(function()use($r,$data,$owner,$ids){
   $leads=Lead::whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();
   abort_unless($leads->count()===count($ids),404);
   foreach($leads as $lead){
    $this->access($r,$lead);$this->transferAccess($r,$lead);
    $this->version($lead,$data['versions'][$lead->id]??null);
   }
   $changed=0;
   foreach($leads as $lead){
    if($lead->owner_id===$owner->id)continue;
    $previous=$lead->owner?->name??'Unassigned';
    $lead->owner_id=$owner->id;$lead->version++;$lead->save();$changed++;
    $this->activity($r,$lead,'Lead assigned',$previous.' → '.$owner->name);
    CrmNotification::create(['user_id'=>$owner->id,'title'=>'Lead assigned: '.$lead->name,'message'=>$r->user()->name.' assigned this lead to you. Open Leads to follow up.','permission'=>'leads.view']);
    CalendarAssignment::create(['actor_id'=>$r->user()->id,'recipient_id'=>$owner->id,'lead_id'=>$lead->id,'kind'=>'Lead','title'=>'Lead assigned: '.$lead->name,'detail'=>$previous.' → '.$owner->name,'link'=>route('leads.show',$lead),'assigned_at'=>now()]);
   }
   return $changed;
  });
  return redirect()->route('leads.index')->with('success',$count.' '.($count===1?'lead':'leads').' transferred to '.$owner->name.'.');
 }
 public function stage(Request $r,Lead $lead){
  $this->access($r,$lead);$data=$r->validate(['stage'=>['required',Rule::in(Lead::STAGES)],'version'=>'required|integer','visit_key'=>'nullable|uuid','after_stage'=>['nullable',Rule::in(['new_list','edit','delete'])]]);
  $item=DB::transaction(function()use($r,$lead,$data){$item=Lead::lockForUpdate()->findOrFail($lead->id);$this->access($r,$item);$this->version($item,$data['version']);if(!empty($data['after_stage']) && $item->stage===$data['stage'])throw \Illuminate\Validation\ValidationException::withMessages(['stage'=>'Choose a different stage before using that action.']);$stageChanged=$item->stage!==$data['stage'];$savedOnWon=$data['stage']==='Won' && !$item->contact_saved_at;if($stageChanged || $savedOnWon){$old=$item->stage;$item->stage=$data['stage'];if($savedOnWon)$item->contact_saved_at=now();$item->version++;$item->save();if($stageChanged)$this->activity($r,$item,'Stage changed',$old.' → '.$item->stage);if($savedOnWon)$this->activity($r,$item,'Saved as contact','Automatically saved when stage changed to Won.');}if($r->filled('visit_key')){$visit=$item->visits()->firstOrCreate(['session_key'=>$r->input('visit_key')],['user_id'=>$r->user()->id,'opened_at'=>now(),'last_seen_at'=>now()]);abort_unless($visit->user_id===$r->user()->id,403);$visit->stage_updated_at=now();$visit->save();}return $item;});
  if(($data['after_stage'] ?? null)==='new_list' && $r->user()->allows('leads.lists'))return redirect(route('leads.lists',['lead_id'=>$item->id]).'#create-list')->with('success','Stage saved. You can now create a list.');
  if(($data['after_stage'] ?? null)==='edit' && $r->user()->allows('leads.edit'))return redirect()->route('leads.edit',$item)->with('success','Stage saved. You can now edit this lead.');
  if(($data['after_stage'] ?? null)==='delete' && $r->user()->allows('leads.delete'))return redirect()->route('leads.show',$item)->with('stage_cleared_lead',$item->id)->with('success','Stage saved. Review the lead before deleting it.');
  if($r->expectsJson())return response()->json(['message'=>'Stage saved.','lead'=>['id'=>$item->id,'stage'=>$item->stage,'version'=>$item->version]]);
  $stageIndex=array_search($item->stage,Lead::STAGES,true);
  $newer=Lead::visibleTo($r->user())->where('stage',$item->stage)->where('id','>',$item->id)->count();
  $url=route('leads.pipeline',['stage_page_'.$stageIndex=>intdiv($newer,10)+1,'highlight'=>$item->id]).'#stage-'.\Illuminate\Support\Str::slug($item->stage);
  return redirect($url)->with('success','Stage saved. Lead is shown in Pipeline.');
 }
 public function work(Request $r){
  $r->validate(['kind'=>['nullable',Rule::in(['Task','Follow-up','Reminder'])],'status'=>['nullable',Rule::in(['Pending','Completed','Cancelled','Overdue','Today'])],'q'=>'nullable|string|max:150']);
  $q=LeadWorkItem::visibleTo($r->user())->with('lead.owner','assignee');
  if($r->filled('kind'))$q->where('kind',$r->kind);
  $status=$r->input('status','Pending');
  if($status==='Overdue')$q->where('status','Pending')->where('due_at','<',now());
  elseif($status==='Today')$q->where('status','Pending')->whereDate('due_at',now()->toDateString());
  elseif($status)$q->where('status',$status);
  if($r->filled('q')){$term='%'.str_replace(['!','%','_'],['!!','!%','!_'],trim($r->q)).'%';$q->where(fn($q)=>$q->whereRaw("title LIKE ? ESCAPE '!'",[$term])->orWhereHas('lead',fn($q)=>$q->whereRaw("name LIKE ? ESCAPE '!'",[$term])));}
  return view('leads.work',['items'=>$q->orderBy('due_at')->paginate(25)->withQueryString(),'owners'=>$this->owners(),'taskStatuses'=>TaskStatus::ordered()->get()]);
 }
 public function tasks(Request $r){$r->merge(['kind'=>'Task']);if(!$r->filled('status'))$r->merge(['status'=>'']);return $this->work($r);}
 public function addWork(Request $r,Lead $lead){
  abort_unless($r->user()->isAdmin(),403);
  $this->access($r,$lead);$data=$this->workData($r);$attachment=$data['attachment']??null;unset($data['attachment']);$data['assigned_to']=$this->workAssignee($r,$lead->owner_id,$data['kind']);
  if($attachment){$data['attachment_path']=$attachment->store('task-attachments');$data['attachment_name']=$attachment->getClientOriginalName();$data['attachment_mime']=$attachment->getMimeType();$data['attachment_size']=$attachment->getSize();}
  DB::transaction(function()use($r,$lead,$data){$lead=Lead::lockForUpdate()->findOrFail($lead->id);$this->access($r,$lead);$item=$lead->workItems()->create($data);$this->notifyAssignment($r,$item);$this->activity($r,$lead,$item->kind.' scheduled',$item->title.' · '.$item->due_at->format('d M Y H:i').' · Assigned to '.($item->assignee?->name ?? 'Unassigned'));});
  return $r->boolean('create_another') ? redirect()->route('tasks.index',['new'=>1])->with('success','Task created. Add another task.') : back()->with('success','Work item scheduled.');
 }
 public function updateWork(Request $r,LeadWorkItem $item){
  $this->workAccess($r,$item);$data=$this->workData($r)+$r->validate(['version'=>'required|integer']);$attachment=$data['attachment']??null;unset($data['attachment']);abort_unless($r->user()->allows(LeadWorkItem::permissionForKind($data['kind'])),403);$data['assigned_to']=$this->workAssignee($r,$item->assigned_to,$data['kind']);
  DB::transaction(function()use($r,$item,$data,$attachment){$lead=Lead::lockForUpdate()->findOrFail($item->lead_id);$item=LeadWorkItem::lockForUpdate()->findOrFail($item->id);$this->workAccess($r,$item);$this->version($item,$data['version']);unset($data['version']);$previousAssignee=$item->assignee?->name ?? 'Unassigned';if($attachment){if($item->attachment_path)Storage::delete($item->attachment_path);$data['attachment_path']=$attachment->store('task-attachments');$data['attachment_name']=$attachment->getClientOriginalName();$data['attachment_mime']=$attachment->getMimeType();$data['attachment_size']=$attachment->getSize();}$item->fill($data);$assigneeChanged=$item->isDirty('assigned_to');if($assigneeChanged)$this->notifyAssignment($r,$item);if($item->isDirty(['due_at','assigned_to']))$item->notified_at=null;$item->version++;$item->save();$newAssignee=$assigneeChanged?(User::find($item->assigned_to)?->name ?? 'Unassigned'):$previousAssignee;$this->activity($r,$lead,$item->kind.' updated',$item->title.' · '.$item->due_at->format('d M Y H:i').($assigneeChanged?' · Assigned: '.$previousAssignee.' → '.$newAssignee:''));});
  return back()->with('success','Work item updated.');
 }
 public function completeWork(Request $r,LeadWorkItem $item){
  $this->workAccess($r,$item);$data=$r->validate(['status'=>['required',Rule::in(['Pending','Completed','Cancelled'])],'outcome'=>'nullable|string|max:10000','version'=>'required|integer']);
  DB::transaction(function()use($r,$item,$data){$lead=Lead::lockForUpdate()->findOrFail($item->lead_id);$item=LeadWorkItem::lockForUpdate()->findOrFail($item->id);$this->workAccess($r,$item);$this->version($item,$data['version']);$wasCompleted=$item->status==='Completed';$item->fill($data);$item->completed_at=$data['status']==='Completed'?now():null;if($data['status']==='Pending')$item->notified_at=null;$item->version++;$item->save();if(!$wasCompleted&&$item->status==='Completed'&&$item->recurring){$next=$item->replicate(['status','outcome','completed_at','notified_at','version','attachment_path','attachment_name','attachment_mime','attachment_size']);$next->status='Pending';$next->outcome=null;$next->completed_at=null;$next->notified_at=null;$next->version=1;$next->due_at=match($item->recurrence_type){'Weekly'=>$item->due_at->copy()->addWeek(),'Monthly'=>$item->due_at->copy()->addMonth(),default=>$item->due_at->copy()->addDay()};$next->save();}$this->activity($r,$lead,$item->kind.' '.$data['status'],$item->title.($item->outcome?' · '.$item->outcome:''));});
  return back()->with('success','Work status updated.');
 }
 public function destroyWork(Request $r,LeadWorkItem $item){
  $this->workAccess($r,$item);abort_unless($item->kind==='Task',422);
  DB::transaction(function()use($r,$item){$lead=Lead::lockForUpdate()->findOrFail($item->lead_id);$entry=LeadWorkItem::lockForUpdate()->findOrFail($item->id);$this->workAccess($r,$entry);$this->activity($r,$lead,'Task deleted',$entry->title);if($entry->attachment_path)Storage::delete($entry->attachment_path);$entry->delete();});
  return back()->with('success','Task deleted.');
 }
 public function downloadWorkAttachment(Request $r,LeadWorkItem $item){$this->workAccess($r,$item);abort_unless($item->attachment_path&&Storage::exists($item->attachment_path),404);return Storage::download($item->attachment_path,$item->attachment_name);}
 public function taskQuickAction(Request $r,LeadWorkItem $item){
  $this->workAccess($r,$item);abort_unless($item->kind==='Task',404);$data=$r->validate(['action'=>['required',Rule::in(['label','assign','status','comment'])],'label'=>'nullable|string|max:80','assigned_to'=>'nullable|exists:users,id','status'=>['nullable',Rule::exists('task_statuses','name')],'comment'=>'nullable|string|max:5000']);
  if($data['action']==='comment'&&!filled($data['comment']??null))throw ValidationException::withMessages(['comment'=>'Enter a comment before submitting.']);
  if($data['action']==='label')$item->label=$data['label']??null;
  if($data['action']==='assign')$item->assigned_to=$data['assigned_to']??null;
  if($data['action']==='status'){$status=TaskStatus::where('name',$data['status']??'')->firstOrFail();$item->status=$status->name;$item->completed_at=$status->is_completed?now():null;}
  if($data['action']==='comment'&&filled($data['comment']??null)){$line='['.now('Asia/Kolkata')->format('d M Y h:i A').'] '.$r->user()->name.': '.$data['comment'];$item->comments=trim(($item->comments? $item->comments."\n\n":'').$line);}
  $item->version++;$item->save();
  if($r->expectsJson())return response()->json(['message'=>'Task updated.','status'=>$item->status]);
  return back()->with('success','Task updated.');
 }
 public function taskAttachment(Request $r,LeadWorkItem $item){$this->workAccess($r,$item);abort_unless($item->kind==='Task',404);$data=$r->validate(['attachment'=>'required|file|max:5120|mimes:jpg,jpeg,png,gif,webp,mp4,mov,pdf,doc,docx,xls,xlsx,ppt,pptx,txt']);if($item->attachment_path)Storage::delete($item->attachment_path);$file=$data['attachment'];$item->attachment_path=$file->store('task-attachments');$item->attachment_name=$file->getClientOriginalName();$item->attachment_mime=$file->getMimeType();$item->attachment_size=$file->getSize();$item->version++;$item->save();return back()->with('success','Task attachment added.');}
 public function saveContact(Request $r,Lead $lead){abort_unless($r->user()->isAdmin(),403);$this->access($r,$lead);DB::transaction(function()use($r,$lead){$item=Lead::lockForUpdate()->findOrFail($lead->id);$this->access($r,$item);if(!$item->contact_saved_at){$item->contact_saved_at=now();$item->version++;$item->save();$this->activity($r,$item,'Saved as contact');}});return back()->with('success','Lead saved to Contacts.');}
 public function updateContactInformation(Request $r,Lead $lead){
  $this->access($r,$lead);
  $data=$r->validate(['name'=>'required|string|max:255','company'=>'nullable|string|max:255','website'=>'nullable|url:http,https|max:500','email'=>'nullable|email|max:255','whatsapp'=>'nullable|string|max:60','phone'=>'nullable|string|max:60','alt_phone'=>'nullable|string|max:60','location'=>'nullable|string|max:255','source'=>'nullable|string|max:100','category'=>'nullable|string|max:100','job_role'=>['nullable',Rule::exists('job_roles','name')],'value'=>'nullable|numeric|min:0|max:9999999999999.99','notes'=>'nullable|string|max:20000','version'=>'required|integer']);
  DB::transaction(function()use($r,$lead,$data){
   $item=Lead::lockForUpdate()->findOrFail($lead->id);$this->access($r,$item);$this->version($item,$data['version']);
   $before=$item->only(['name','company','website','email','whatsapp','phone','alt_phone','location','source','category','job_role','value','notes']);
   $item->fill(collect($data)->except('version')->all());[$item->identity_phone,$item->identity_profile]=LeadIdentity::keys($item->name,$item->company,$item->phone,$item->location);$item->version++;$item->save();
   $changed=collect($before)->filter(fn($value,$field)=>(string)$value!==(string)$item->$field)->keys()->map(fn($field)=>ucwords(str_replace('_',' ',$field)))->implode(', ');
   if($changed)$this->activity($r,$item,'Contact information updated',$changed);
  });
  return redirect()->route('leads.show',$lead)->with('success','Contact information and lead score updated.');
 }
 private function workAccess(Request $r,LeadWorkItem $item):void {if($r->user()->isAdmin())return;abort_unless($item->assigned_to===$r->user()->id,404);abort_unless($r->user()->allows(LeadWorkItem::permissionForKind($item->kind)),403);}
 private function transferAccess(Request $r,Lead $lead):void {abort_unless($lead->owner_id===$r->user()->id || $r->user()->allows('leads.assign'),403);}
 private function workAssignee(Request $r,?int $current,string $kind):?int {
  $data=$r->validate(['assigned_to'=>'nullable|integer|exists:users,id']);
  if(!$r->user()->isAdmin()){abort_if(isset($data['assigned_to']) && (int)$data['assigned_to']!==$current,403);return $current;}
  $id=$data['assigned_to']??$current;$user=$id?User::find($id):null;
  if(!$user?->allows('leads.view') || !$user?->allows(LeadWorkItem::permissionForKind($kind)))throw ValidationException::withMessages(['assigned_to'=>'Choose an active user with access to this action type.']);return $user->id;
 }
 private function notifyAssignment(Request $r,LeadWorkItem $item):void {if($item->assigned_to){CrmNotification::create(['user_id'=>$item->assigned_to,'title'=>$item->kind.' assigned: '.$item->title,'message'=>$r->user()->name.' assigned this action to you. Open Follow-ups, Tasks & Reminders.','permission'=>'leads.view']);CalendarAssignment::create(['actor_id'=>$r->user()->id,'recipient_id'=>$item->assigned_to,'lead_id'=>$item->lead_id,'kind'=>$item->kind,'title'=>$item->kind.' assigned: '.$item->title,'detail'=>$item->notes,'link'=>route('leads.work',['q'=>$item->title]),'assigned_at'=>now(),'due_at'=>$item->due_at]);}}
 private function workData(Request $r): array {$data=$r->validate(['kind'=>['required',Rule::in(['Task','Follow-up','Reminder'])],'title'=>'required|string|max:255','due_at'=>'required|date_format:Y-m-d\TH:i','priority'=>['required',Rule::in(['Low','Normal','High'])],'label'=>'nullable|string|max:80','notes'=>'nullable|string|max:10000','recurring'=>'nullable|boolean','recurrence_type'=>['nullable',Rule::in(['Daily','Weekly','Monthly'])],'attachment'=>'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,mp4,mov,pdf,doc,docx,xls,xlsx,ppt,pptx,txt']);$data['recurring']=filled($data['recurrence_type']??null);if(!$data['recurring'])$data['recurrence_type']=null;return $data;}
 private function version($item,$version): void {if($item->version!==(int)$version)throw ValidationException::withMessages(['version'=>'This record changed elsewhere. Refresh before saving.']);}
 private function activity(Request $r,Lead $lead,string $action,?string $details=null): void {$lead->activities()->create(['actor'=>$r->user()->name,'actor_id'=>$r->user()->id,'action'=>$action,'details'=>$details]);}
}
