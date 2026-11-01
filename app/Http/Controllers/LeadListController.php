<?php
namespace App\Http\Controllers;

use App\Models\{Lead,LeadList,LeadListAutomation,LeadListDelivery,LeadSetting};
use App\Support\{LeadListMessenger,LeadWhatsAppLink};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class LeadListController {
 private function access(Request $r,LeadList $list):void {abort_unless($r->user()->isAdmin()||$list->created_by===$r->user()->id,404);}
 public function index(Request $r){
  $r->validate(['lead_id'=>'nullable|integer']);
  $selectedLead=$r->filled('lead_id')?Lead::visibleTo($r->user())->findOrFail($r->integer('lead_id')):null;
  $lists=LeadList::when(!$r->user()->isAdmin(),fn($q)=>$q->where('created_by',$r->user()->id))->withCount('members')->latest('updated_at')->get();
  return view('leads.lists',['lists'=>$lists,'selectedLead'=>$selectedLead]);
 }
 public function store(Request $r){
  abort_unless($r->user()->allows('leads.lists'),403);
  $data=$r->validate(['name'=>'required|string|max:150','description'=>'required|string|max:500','lead_id'=>'nullable|integer']);
  $leadId=$data['lead_id']??null;unset($data['lead_id']);
  if($leadId)abort_unless(Lead::visibleTo($r->user())->whereKey($leadId)->exists(),404);
  $list=DB::transaction(function()use($data,$leadId,$r){$created=LeadList::create($data+['created_by'=>$r->user()->id]);if($leadId)$created->members()->attach($leadId);return $created;});
  return redirect()->route('leads.lists.show',$list)->with('success','List created. Choose contacts to add.');
 }
 public function show(Request $r,LeadList $list){
  $this->access($r,$list);
  $list->load(['automations'=>fn($q)=>$q->latest(),'deliveries'=>fn($q)=>$q->with('lead')->latest()->limit(25)]);
  $members=$list->members()->visibleTo($r->user())->orderBy('name')->get();
  $r->validate(['q'=>'nullable|string|max:100']);
  $available=Lead::visibleTo($r->user())->whereNotIn('id',$list->members()->select('leads.id'))
   ->when($r->filled('q'),function($query)use($r){$term='%'.str_replace(['!','%','_'],['!!','!%','!_'],trim($r->q)).'%';$query->where(fn($q)=>$q->whereRaw("name LIKE ? ESCAPE '!'",[$term])->orWhereRaw("company LIKE ? ESCAPE '!'",[$term])->orWhereRaw("email LIKE ? ESCAPE '!'",[$term]));})
   ->orderBy('name')->limit(100)->get();
  return view('leads.list-show',['list'=>$list,'members'=>$members,'available'=>$available,'settings'=>LeadSetting::findOrFail(1)]);
 }
 public function update(Request $r,LeadList $list){$this->access($r,$list);abort_unless($r->user()->allows('leads.lists'),403);$list->update($r->validate(['name'=>'required|string|max:150','description'=>'required|string|max:500']));return back()->with('success','List updated. Future messages will use the new description.');}
 public function add(Request $r,LeadList $list){
  $this->access($r,$list);abort_unless($r->user()->allows('leads.lists'),403);
  $data=$r->validate(['lead_ids'=>'required|array|min:1|max:200','lead_ids.*'=>'integer|distinct']);
  $ids=array_map('intval',$data['lead_ids']);abort_unless(Lead::visibleTo($r->user())->whereIn('id',$ids)->count()===count($ids),404);
  DB::transaction(function()use($list,$ids,$r){
   $attached=$list->members()->syncWithoutDetaching($ids)['attached'];
   foreach($attached as $id)Lead::findOrFail($id)->activities()->create(['actor'=>$r->user()->name,'actor_id'=>$r->user()->id,'kind'=>'System','action'=>'Added to list','details'=>$list->name]);
  });
  return back()->with('success',count($ids).' contacts added.');
 }
 public function remove(Request $r,LeadList $list,Lead $lead){$this->access($r,$list);abort_unless($r->user()->allows('leads.lists'),403);abort_unless(Lead::visibleTo($r->user())->whereKey($lead->id)->exists(),404);DB::transaction(function()use($r,$list,$lead){if($list->members()->detach($lead->id))$lead->activities()->create(['actor'=>$r->user()->name,'actor_id'=>$r->user()->id,'kind'=>'System','action'=>'Removed from list','details'=>$list->name]);});return back()->with('success','Contact removed from list.');}
 public function destroy(Request $r,LeadList $list){$this->access($r,$list);abort_unless($r->user()->allows('leads.lists'),403);$list->delete();return redirect()->route('leads.lists')->with('success','List removed.');}
 public function toggleActive(Request $r,LeadList $list){$this->access($r,$list);abort_unless($r->user()->allows('leads.lists'),403);$list->active=!$list->active;$list->save();return back()->with('success',$list->active?'List activated.':'List deactivated. Sending and schedules are paused.');}
 public function send(Request $r,LeadList $list){
  $this->access($r,$list);abort_unless($r->user()->allows('leads.broadcast'),403);
  abort_unless($list->active,409,'Activate this list before sending.');
  $data=$r->validate(['channel'=>['required',Rule::in(['Email','WhatsApp'])],'lead_ids'=>'required|array|min:1|max:50','lead_ids.*'=>'integer|distinct','request_key'=>'required|uuid']);
  $ids=array_map('intval',$data['lead_ids']);$leads=$list->members()->visibleTo($r->user())->whereIn('leads.id',$ids)->get();abort_unless($leads->count()===count($ids),404);
  if($data['channel']==='WhatsApp')return $this->whatsappLinks($list,$leads);
  if(!LeadListMessenger::ready($data['channel'],LeadSetting::findOrFail(1)))throw ValidationException::withMessages(['channel'=>'Configure '.$data['channel'].' in Lead settings first.']);
  if(!Cache::add('lead-list-send:'.$data['request_key'],true,now()->addMinutes(15)))return back()->with('success','This send request was already submitted. Check recent sends before trying again.');
  $accepted=0;$issues=[];
  foreach($leads as $lead){try{$delivery=LeadListMessenger::send($list,$lead,$data['channel'],$r->user());if($delivery->status==='Accepted')$accepted++;else $issues[]=$lead->name.': delivery unconfirmed';}catch(ValidationException $e){$issues[]=$lead->name.': '.implode(' ',$e->errors()['recipient']??['invalid recipient']);}}
  return back()->with('success',$accepted.' of '.$leads->count().' messages accepted by '.$data['channel'].' provider.')->with('send_issues',$issues);
 }
 public function sendAll(Request $r,LeadList $list){
  $this->access($r,$list);abort_unless($r->user()->allows('leads.broadcast'),403);
  abort_unless($list->active,409,'Activate this list before sending.');
  $data=$r->validate(['channel'=>['required',Rule::in(['Email','WhatsApp'])],'request_key'=>'required|uuid']);
  if($data['channel']==='WhatsApp')return $this->whatsappLinks($list,$list->members()->visibleTo($r->user())->orderBy('name')->get());
  if(!LeadListMessenger::ready($data['channel'],LeadSetting::findOrFail(1)))throw ValidationException::withMessages(['channel'=>'Configure '.$data['channel'].' in Lead settings first.']);
  if(!Cache::add('lead-list-send:'.$data['request_key'],true,now()->addMinutes(15)))return back()->with('success','This send request was already submitted.');
  $count=0;
  $list->members()->visibleTo($r->user())->orderBy('leads.id')->chunk(100,function($members)use($list,$r,$data,&$count){
   $rows=[];$time=now();
   foreach($members as $lead){$rows[]=['lead_list_id'=>$list->id,'lead_id'=>$lead->id,'sent_by'=>$r->user()->id,'channel'=>$data['channel'],'recipient'=>(string)($data['channel']==='Email'?$lead->email:($lead->whatsapp?:$lead->phone)),'message'=>$list->description,'status'=>'Pending','created_at'=>$time,'updated_at'=>$time];$count++;}
   LeadListDelivery::insert($rows);
  });
  return back()->with('success',$count.' contacts queued. Messages will send in small batches and appear in Recent sends.');
 }
 public function settings(Request $r){abort_unless($r->user()->isAdmin(),403);return view('leads.whatsapp-settings',['settings'=>LeadSetting::findOrFail(1)]);}
 private function whatsappLinks(LeadList $list,$leads){
  $contacts=$leads->map(fn($lead)=>['lead'=>$lead,'url'=>LeadWhatsAppLink::for($lead,$list->description)]);
  return view('leads.whatsapp-links',['list'=>$list,'contacts'=>$contacts]);
 }
 public function whatsappQueue(Request $r,LeadList $list){
  $this->access($r,$list);abort_unless($r->user()->allows('leads.broadcast'),403);
  abort_unless($list->active,409,'Activate this list before opening chats.');
  return $this->whatsappLinks($list,$list->members()->visibleTo($r->user())->orderBy('name')->get());
 }
 public function saveSettings(Request $r){
  abort_unless($r->user()->isAdmin(),403);
  $data=$r->validate(['phone_number_id'=>'required|digits_between:8,30','api_version'=>'required|regex:/^v\d+\.\d+$/','template_name'=>'required|regex:/^[a-z0-9_]+$/|max:255','language_code'=>'required|regex:/^[a-z]{2}_[A-Z]{2}$/','token'=>'nullable|string|max:2000']);
  $settings=LeadSetting::findOrFail(1);if(filled($data['token']??null))$settings->whatsapp_token=$data['token'];unset($data['token']);$settings->whatsapp_config=$data;$settings->save();
  return back()->with('success','WhatsApp settings saved. Send a test to a permitted contact before activating schedules.');
 }
 public function automation(Request $r,LeadList $list){
  $this->access($r,$list);abort_unless($r->user()->allows('leads.automations')&&$r->user()->allows('leads.broadcast'),403);
  abort_unless($list->active,409,'Activate this list before scheduling.');
  $data=$r->validate(['channel'=>['required',Rule::in(['Email','WhatsApp'])],'frequency'=>['required',Rule::in(['Once','Daily','Weekly','Monthly'])],'send_time'=>'required|date_format:H:i','day_of_week'=>'nullable|integer|min:0|max:6','day_of_month'=>'nullable|integer|min:1|max:28']);
  if(!LeadListMessenger::ready($data['channel'],LeadSetting::findOrFail(1)))throw ValidationException::withMessages(['channel'=>'Ask an administrator to connect '.$data['channel'].' before scheduling automatic messages.']);
  $data['active']=true;$data['next_run_at']=self::nextRun($data,now('Asia/Kolkata'));
  $list->automations()->create($data);return back()->with('success',$data['channel'].' automation scheduled. Messages will be sent automatically at the chosen time.');
 }
 public static function nextRun(array $data,\Carbon\CarbonInterface $from):\Carbon\CarbonInterface {
  $next=$from->copy()->setTimeFromTimeString($data['send_time'])->startOfMinute();
  if($next->lessThanOrEqualTo($from))$next->addDay();
  if($data['frequency']==='Weekly')while($next->dayOfWeek!==(int)($data['day_of_week']??1))$next->addDay();
  if($data['frequency']==='Monthly')while($next->day!==(int)($data['day_of_month']??1))$next->addDay();
  return $next;
 }
 public function toggle(Request $r,LeadList $list,LeadListAutomation $automation){
  $this->access($r,$list);abort_unless($r->user()->allows('leads.automations'),403);abort_unless($automation->lead_list_id===$list->id,404);abort_if(!$list->active && !$automation->active,409,'Activate this list before resuming schedules.');$automation->active=!$automation->active;
  if($automation->active&&!LeadListMessenger::ready($automation->channel,LeadSetting::findOrFail(1)))throw ValidationException::withMessages(['channel'=>'Connect '.$automation->channel.' before resuming automatic messages.']);
  if($automation->active)$automation->next_run_at=self::nextRun($automation->toArray(),now('Asia/Kolkata'));
  $automation->save();return back()->with('success',$automation->active?'Automation resumed.':'Automation paused.');
 }
}
