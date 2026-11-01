<?php
namespace App\Support;

use App\Models\{LeadListDelivery,LeadSetting};
use Illuminate\Support\Facades\DB;

final class LeadListQueue {
 public static function run():int {
  $count=0;
  foreach(LeadListDelivery::where('status','Pending')->orderBy('id')->limit(25)->pluck('id') as $id){
   $delivery=DB::transaction(function()use($id){$item=LeadListDelivery::lockForUpdate()->find($id);if(!$item||$item->status!=='Pending')return null;$item->status='Sending';$item->save();return $item;});
   if(!$delivery)continue;
   $list=$delivery->list;$lead=$delivery->lead;$actor=$delivery->sender;
   if(!$list?->active){$delivery->update(['status'=>'Skipped','error'=>'List is deactivated.']);continue;}
   if(!$list||!$lead||!$actor?->allows('leads.broadcast')||!\App\Models\Lead::visibleTo($actor)->whereKey($lead->id)->exists()){$delivery->update(['status'=>'Skipped','error'=>'Contact or sender no longer available.']);continue;}
   if(!LeadListMessenger::ready($delivery->channel,LeadSetting::findOrFail(1))){$delivery->update(['status'=>'Skipped','error'=>'Channel connection is unavailable.']);continue;}
   try{LeadListMessenger::send($list,$lead,$delivery->channel,$actor,null,$delivery);$count++;}catch(\Throwable $e){$delivery->update(['status'=>'Skipped','error'=>mb_substr($e->getMessage(),0,1000)]);}
  }
  return $count;
 }
}
