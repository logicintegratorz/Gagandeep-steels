<?php
namespace App\Http\Controllers;
use App\Models\LeadSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class LeadSettingsController {
 public function index(Request $r){abort_unless($r->user()->isAdmin(),403);return view('leads.settings',['settings'=>LeadSetting::findOrFail(1),'backupSettings'=>\App\Models\LeadBackupSetting::findOrFail(1),'backups'=>\App\Models\LeadBackup::latest('backed_up_at')->paginate(15)]);}
 public function save(Request $r){
  abort_unless($r->user()->isAdmin(),403);
  $data=$r->validate(['section'=>'required|in:weights,email,tenure','version'=>'required|integer']);
  if($data['section']==='tenure'){
   $values=$r->validate(['backup_after_days'=>'required|integer|min:1|max:3650','delete_after_days'=>'required|integer|min:1|max:3650']);
   DB::transaction(function()use($data,$values){$settings=\App\Models\LeadBackupSetting::lockForUpdate()->findOrFail(1);if($settings->version!==(int)$data['version'])throw ValidationException::withMessages(['version'=>'Backup settings changed elsewhere. Refresh and try again.']);$settings->fill($values);$settings->version++;$settings->save();\App\Models\LeadBackup::query()->orderBy('id')->chunkById(100,function($backups)use($values){foreach($backups as $backup){$backup->purge_at=$backup->backed_up_at->copy()->addDays($values['delete_after_days']);$backup->save();}});});
   return back()->with('success','Lead tenure and backup retention saved.');
  }
  $weights=[];$email=[];
  if($data['section']==='weights'){
   $rules=['weights'=>'nullable|array:'.implode(',',array_keys(LeadSetting::FIELDS))];foreach(LeadSetting::FIELDS as $field=>$label)$rules['weights.'.$field]='nullable|integer|min:0|max:100';
   $values=$r->validate($rules);$weights=[];foreach(LeadSetting::FIELDS as $field=>$label)$weights[$field]=(int)($values['weights'][$field]??0);
   if(array_sum($weights)>100)throw ValidationException::withMessages(['weights'=>'The combined contact weightage cannot exceed 100. Reduce one or more fields before saving.']);
  }else{
   $email=$r->validate(['host'=>'required|string|max:255','port'=>'required|integer|in:465,587','username'=>'required|string|max:255','password'=>'nullable|string|max:1000','from_address'=>'required|email|max:255','from_name'=>'required|string|max:255']);
   $savedUsername=LeadSetting::findOrFail(1)->email_config['username']??null;
   if($savedUsername && strcasecmp(trim($savedUsername),trim($email['username']))!==0 && blank($email['password']??null)){
    throw ValidationException::withMessages(['password'=>'SMTP username changed. Enter the new account’s app password; leaving this blank would keep the previous account’s password.']);
   }
  }
  DB::transaction(function()use($r,$data,$weights,$email){
   $settings=LeadSetting::lockForUpdate()->findOrFail(1);
   if($settings->version!==(int)$data['version'])throw ValidationException::withMessages(['version'=>'Settings changed elsewhere. Refresh before saving.']);
   if($data['section']==='weights')$settings->weights=$weights;
   else{if(filled($email['password']??null))$settings->email_password=$email['password'];unset($email['password']);$settings->email_config=$email;}
   $settings->version++;$settings->save();
  });
  if($r->expectsJson())return response()->json(['saved'=>true,'version'=>LeadSetting::findOrFail(1)->version]);
  return back()->with('success','Lead settings saved.');
 }
}
