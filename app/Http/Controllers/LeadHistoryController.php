<?php
namespace App\Http\Controllers;

use App\Models\{CalendarAssignment,CrmAuditEvent,Lead,LeadActivity,LeadListDelivery,User};
use Illuminate\Http\Request;

final class LeadHistoryController {
 public function __invoke(Request $request,string $lead){
  abort_unless($request->user()->isAdmin(),403);
  $lead=Lead::withTrashed()->findOrFail($lead);
  $lead->load('creator','owner');
  $url=route('leads.show',$lead);
  $events=collect([[
   'at'=>$lead->created_at,'title'=>$lead->connection_id?'Lead imported':'Lead created',
   'actor'=>$lead->creator?->name ?? ($lead->connection_id?'Connected lead source':'Unknown'),
   'details'=>trim(($lead->source ? 'Source: '.$lead->source.' · ' : '').'Lead: '.$lead->name),
   'user_id'=>$lead->created_by,
  ]]);
  $assignments=CalendarAssignment::with('actor','recipient')->where(fn($query)=>$query->where('lead_id',$lead->id)->orWhere(fn($legacy)=>$legacy->whereNull('lead_id')->where('kind','Lead')->where('link',$url)))->orderBy('assigned_at')->get();
  foreach(LeadActivity::withTrashed()->with('actorUser')->where('lead_id',$lead->id)->where('action','!=','Lead viewed')->orderBy('created_at')->get() as $activity){
   if(in_array($activity->action,['Lead assigned','Lead assigned automatically'],true) && $assignments->contains(fn($assignment)=>$assignment->kind==='Lead' && abs($assignment->assigned_at->diffInSeconds($activity->created_at))<=5))continue;
   $events->push(['at'=>$activity->created_at,'title'=>$activity->action.($activity->trashed()?' (record removed)':''),'actor'=>$activity->actorUser?->name ?? $activity->actor,'details'=>$activity->details,'user_id'=>$activity->actor_id]);
  }
  foreach($assignments as $assignment){
   $title=$assignment->kind==='Lead'?(str_contains((string)$assignment->detail,'claimed on first open') || $assignment->detail==='Claimed when opened'?'Lead claimed automatically':'Lead transferred'):$assignment->kind.' assigned';
   $details=$assignment->kind==='Lead'?($assignment->detail ?: 'Assigned to '.($assignment->recipient?->name ?? 'Former user')):($assignment->title.' → '.($assignment->recipient?->name ?? 'Former user').($assignment->detail?' · '.$assignment->detail:''));
   $events->push(['at'=>$assignment->assigned_at,'title'=>$title,'actor'=>$assignment->actor?->name ?? 'Former user','details'=>$details,'user_id'=>$assignment->actor_id,'recipient_id'=>$assignment->recipient_id]);
  }
  foreach($lead->visits()->with('user')->orderBy('opened_at')->get() as $visit){
   $actor=$visit->user?->name ?? 'Former user';
   $events->push(['at'=>$visit->opened_at,'title'=>'Lead opened','actor'=>$actor,'details'=>'Lead details opened by '.$actor,'user_id'=>$visit->user_id]);
   if($visit->stage_updated_at)$events->push(['at'=>$visit->stage_updated_at,'title'=>'Stage updated during visit','actor'=>$actor,'details'=>null,'user_id'=>$visit->user_id]);
  }
  foreach(CrmAuditEvent::with('user')->where('link',$url)->orderBy('occurred_at')->get() as $audit){
   $events->push(['at'=>$audit->occurred_at,'title'=>$audit->action,'actor'=>$audit->user?->name ?? 'Former user','details'=>null,'user_id'=>$audit->user_id]);
  }
  foreach(LeadListDelivery::with('sender','list')->where('lead_id',$lead->id)->orderBy('created_at')->get() as $delivery){
   $events->push(['at'=>$delivery->created_at,'title'=>$delivery->channel.' list message · '.$delivery->status,'actor'=>$delivery->sender?->name ?? ($delivery->automation_id?'Automation':'Former user'),'details'=>'List: '.($delivery->list?->name ?? 'Deleted list').' · Recipient: '.$delivery->recipient,'user_id'=>$delivery->sent_by]);
  }
  $users=User::whereIn('id',$events->pluck('user_id')->merge($events->pluck('recipient_id'))->filter()->unique())->orWhereIn('name',$events->pluck('actor')->unique())->orderBy('name')->get(['id','name']);
  $selected=$request->integer('user');
  if($selected)$events=$events->filter(fn($event)=>($event['user_id'] ?? null)===$selected || ($event['recipient_id'] ?? null)===$selected || (($event['user_id'] ?? null)===null && $users->firstWhere('id',$selected)?->name===$event['actor']));
  $events=$events->sort(fn($a,$b)=>$a['at']->getTimestamp()<=>$b['at']->getTimestamp())->values();
  return response()->view('leads.history-modal',['lead'=>$lead,'events'=>$events,'users'=>$users,'selected'=>$selected])->header('Cache-Control','private, no-store');
 }
}
