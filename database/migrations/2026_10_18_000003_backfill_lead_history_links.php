<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
 public function up():void {
  $names=DB::table('users')->select('id','name')->get()->groupBy('name');
  foreach($names as $name=>$users){
   if($users->count()===1)DB::table('lead_activities')->whereNull('actor_id')->where('actor',$name)->update(['actor_id'=>$users->first()->id]);
  }
  DB::table('calendar_assignments')->whereNull('lead_id')->orderBy('id')->chunkById(100,function($assignments){
   foreach($assignments as $assignment){
    $leadId=null;
    if($assignment->kind==='Lead' && preg_match('~/leads/(\d+)(?:$|[/?#])~',(string)$assignment->link,$matches))$leadId=(int)$matches[1];
    elseif(in_array($assignment->kind,['Task','Follow-up','Reminder'],true)){
     $prefix=$assignment->kind.' assigned: ';
     if(str_starts_with($assignment->title,$prefix)){
      $matches=DB::table('lead_work_items')->where('kind',$assignment->kind)->where('title',substr($assignment->title,strlen($prefix)))->where('assigned_to',$assignment->recipient_id)->limit(2)->pluck('lead_id');
      if($matches->count()===1)$leadId=$matches->first();
     }
    }
    if($leadId && DB::table('leads')->where('id',$leadId)->exists())DB::table('calendar_assignments')->where('id',$assignment->id)->update(['lead_id'=>$leadId]);
   }
  });
 }
 public function down():void {}
};
