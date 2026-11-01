<?php
namespace App\Support;

use App\Http\Controllers\LeadListController;
use App\Models\{Lead,LeadListAutomation,LeadSetting};
use Illuminate\Support\Facades\DB;

final class LeadListAutomations {
 public static function run():int {
  $done=0;
  foreach(LeadListAutomation::where('active',true)->where('next_run_at','<=',now('Asia/Kolkata'))->orderBy('next_run_at')->limit(10)->pluck('id') as $id){
   $candidate=LeadListAutomation::with('list.creator')->find($id);
   if(!$candidate||!$candidate->list?->active||!$candidate->list->creator?->active||!$candidate->list->creator?->allows('leads.broadcast')||!LeadListMessenger::ready($candidate->channel,LeadSetting::findOrFail(1)))continue;
   $claimed=DB::transaction(function()use($id){
    $item=LeadListAutomation::lockForUpdate()->find($id);
    if(!$item||!$item->active||$item->next_run_at?->isFuture())return null;
    if($item->frequency==='Once'){$item->active=false;$item->next_run_at=null;}
    else $item->next_run_at=LeadListController::nextRun($item->toArray(),now('Asia/Kolkata'));
    $item->save();
    return $item;
   });
   if(!$claimed||!$claimed->list)continue;
   $creator=$claimed->list->creator;
   if(!$creator||!$creator->active||!$creator->allows('leads.broadcast'))continue;
   $claimed->list->members()->orderBy('leads.id')->chunk(50,function($members)use($claimed,$creator,&$done){
    foreach($members as $lead){if(!Lead::visibleTo($creator)->whereKey($lead->id)->exists())continue;
     try{LeadListMessenger::send($claimed->list,$lead,$claimed->channel,$creator,$claimed);$done++;}catch(\Throwable $error){} }
   });
  }
  return $done;
 }
}
