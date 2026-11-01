<?php
namespace App\Http\Controllers;

use App\Models\{CalendarAssignment,Lead,LeadActivity,LeadVisit,LeadWorkItem,User};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class DashboardCalendarController {
 public function leads(Request $request){
  abort_unless($request->user()->isAdmin(),403);
  $data=$request->validate(['q'=>'required|string|min:2|max:100']);
  $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],trim($data['q'])).'%';
  return response()->json(Lead::visibleTo($request->user())->where(fn($query)=>$query->whereRaw("name LIKE ? ESCAPE '!'",[$term])->orWhereRaw("company LIKE ? ESCAPE '!'",[$term]))->orderBy('name')->limit(20)->get(['id','name','company']))->header('Cache-Control','private, no-store');
 }
 public function index(Request $request){
  $data=$request->validate(['start'=>'required|date_format:Y-m-d','end'=>'required|date_format:Y-m-d|after_or_equal:start','user_id'=>'nullable|integer']);
  $start=CarbonImmutable::parse($data['start'],'Asia/Kolkata')->startOfDay();
  $end=CarbonImmutable::parse($data['end'],'Asia/Kolkata')->endOfDay();
  abort_if($start->diffInDays($end)>42,422);
  $viewer=$request->user();
  $selected=$viewer->isAdmin()?(int)($data['user_id']??0):$viewer->id;
  if($selected)abort_unless(User::whereKey($selected)->where('active',true)->exists(),422);
  $events=collect();
  $visibleLeads=fn()=> $viewer->allows('leads.view') ? Lead::visibleTo($viewer) : Lead::query()->whereRaw('1=0');
  $push=function($at,$kind,$title,$lead,$detail,$person,$status='',$url=null)use($events){
   $events->push(['date'=>$at->format('Y-m-d'),'time'=>$at->format('H:i'),'kind'=>$kind,'title'=>$title,'lead'=>$lead->name,'detail'=>$detail,'person'=>$person,'status'=>$status,'url'=>$url ?: route('leads.show',$lead)]);
  };
  $leads=$visibleLeads()->whereBetween('created_at',[$start,$end])->with('creator','owner')->when($selected,fn($q)=>$q->where('owner_id',$selected))->get();
  foreach($leads as $lead)$push($lead->created_at,'Lead','Lead added: '.$lead->name,$lead,$lead->company.' · Added by '.($lead->creator?->name ?: 'Import'),$lead->owner?->name ?: 'Unassigned');
  $saved=$visibleLeads()->whereBetween('contact_saved_at',[$start,$end])->with('creator','owner')->get();
  $savedActors=LeadActivity::whereIn('lead_id',$saved->pluck('id'))->where('action','Saved as contact')->orderByDesc('id')->get()->unique('lead_id')->keyBy('lead_id');
  $selectedName=$selected?User::find($selected)?->name:null;
  foreach($saved as $lead){
   $person=$savedActors->get($lead->id)?->actor ?: $lead->creator?->name ?: $lead->owner?->name ?: 'Unknown';
   if($selected && $person!==$selectedName)continue;
   $push(CarbonImmutable::parse($lead->contact_saved_at),'Lead','Saved as contact: '.$lead->name,$lead,$lead->company ?: 'Contact saved',$person);
  }
  $work=LeadWorkItem::visibleTo($viewer)->whereBetween('due_at',[$start,$end])->with('lead','assignee')->when($selected,fn($q)=>$q->where('assigned_to',$selected))->when(!$viewer->allows('leads.view'),fn($q)=>$q->whereRaw('1=0'))->get();
  foreach($work as $item){
   if(!$viewer->allows(LeadWorkItem::permissionForKind($item->kind)))continue;
   $detail=implode("\n",array_filter(['Priority: '.$item->priority,$item->notes,$item->outcome?'Outcome: '.$item->outcome:null]));
   $push($item->due_at,$item->kind,$item->title,$item->lead,$detail,$item->assignee?->name ?: 'Unassigned',$item->status,$viewer->isAdmin()?null:route('leads.work',['q'=>$item->title]));
  }
  if($viewer->allows('leads.activity')){
   $actor=$selected?User::find($selected)?->name:null;
   $activities=LeadActivity::whereHas('lead',fn($q)=>$q->whereIn('id',$visibleLeads()->select('id')))->whereBetween('created_at',[$start,$end])->with('lead')->when($actor,fn($q)=>$q->where('actor',$actor))->where('action','!=','Saved as contact')->get();
   foreach($activities as $activity)$push($activity->created_at,'Activity',$activity->action,$activity->lead,$activity->details ?: ($activity->kind ?: 'Activity'),$activity->actor);
  }
  $visits=LeadVisit::with('lead','user')->whereHas('lead',fn($query)=>$query->whereIn('id',$visibleLeads()->select('id')))->whereBetween('opened_at',[$start,$end])->when($selected,fn($query)=>$query->where('user_id',$selected))->get();
  foreach($visits as $visit)$push($visit->opened_at,'View','Opened lead: '.$visit->lead->name,$visit->lead,'Lead details opened',$visit->user?->name ?: 'Former user',$visit->status());
  $assignments=CalendarAssignment::with('actor','recipient')->whereBetween('assigned_at',[$start,$end])->when($selected,fn($query)=>$query->where('recipient_id',$selected))->when(!$viewer->isAdmin(),fn($query)=>$query->where('recipient_id',$viewer->id))->get();
  foreach($assignments as $assignment)$events->push(['date'=>$assignment->assigned_at->format('Y-m-d'),'time'=>$assignment->assigned_at->format('H:i'),'kind'=>'Change','title'=>$assignment->title,'lead'=>$assignment->kind.' assignment','detail'=>implode("\n",array_filter(['Assigned by '.($assignment->actor?->name ?: 'Administrator'),$assignment->detail,$assignment->due_at?'Due '.$assignment->due_at->format('d M Y H:i').' IST':null])),'person'=>$assignment->recipient?->name ?: 'Former user','status'=>'Assigned','url'=>$assignment->link]);
  return response()->json(['events'=>$events->sortBy([['date','asc'],['time','asc']])->values()])->header('Cache-Control','private, no-store');
 }
}
