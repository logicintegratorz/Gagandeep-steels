<?php
namespace App\Support;
use App\Models\{Lead,LeadBackup,LeadBackupSetting};
final class LeadTenure {
 public static function run():array{
  $settings=LeadBackupSetting::findOrFail(1);$created=0;
  Lead::withTrashed()->where('created_at','<=',now()->subDays($settings->backup_after_days))->whereNull('contact_saved_at')->where('stage','!=','Won')->whereDoesntHave('workItems')->whereDoesntHave('activities',fn($q)=>$q->where('kind','!=','System'))->orderBy('id')->chunkById(100,function($leads)use($settings,&$created){
   foreach($leads as $lead){
    $backup=LeadBackup::firstOrCreate(['lead_id'=>$lead->id],[
     'source'=>$lead->source?:'Manual','status'=>$lead->trashed()?'Deleted':($lead->stage==='Lost'?'Lost':'Backed up'),
     'snapshot'=>$lead->getAttributes(),'backed_up_at'=>now(),'purge_at'=>now()->addDays($settings->delete_after_days),
    ]);
    if(!$backup->wasRecentlyCreated){$backup->update(['source'=>$lead->source?:'Manual','status'=>$lead->trashed()?'Deleted':($lead->stage==='Lost'?'Lost':'Backed up'),'snapshot'=>$lead->getAttributes()]);}else{$created++;}
   }
  });
  $purged=LeadBackup::where('purge_at','<=',now())->delete();
  return compact('created','purged');
 }
}
