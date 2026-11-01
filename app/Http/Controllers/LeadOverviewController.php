<?php
namespace App\Http\Controllers;
use App\Models\{Lead,LeadSetting,LeadActivity};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Mail};
use Illuminate\Validation\ValidationException;
final class LeadOverviewController {
 public function store(Request $r,Lead $lead){
  abort_unless(Lead::visibleTo($r->user())->whereKey($lead->id)->exists(),404);
  $data=$r->validate(['kind'=>'required|in:Note,Call log,Customer note,Email','call_direction'=>'required_if:kind,Call log|nullable|in:Incoming,Outgoing,Missed call,Message sent,Message received','subject'=>'nullable|string|max:255','body'=>'required|string|max:20000','request_key'=>'required|uuid','version'=>'required_if:kind,Email|nullable|integer']);
  if($data['kind']==='Call log' && mb_strlen(trim($data['subject']??''))>240)throw ValidationException::withMessages(['subject'=>'Call title must be 240 characters or fewer.']);
  $settings=LeadSetting::findOrFail(1);
  if($data['kind']==='Email'){
   abort_unless($r->user()->allows('leads.email'),403);
   if(!$settings->emailReady())throw ValidationException::withMessages(['email'=>'Ask your administrator to configure SMTP in Lead settings.']);
   if(!filter_var($lead->email,FILTER_VALIDATE_EMAIL))throw ValidationException::withMessages(['email'=>'Add a valid email address to this lead first.']);
   if(!filled($data['subject']??null))throw ValidationException::withMessages(['subject'=>'An email subject is required.']);
  }
  $entry=DB::transaction(function()use($r,$lead,$data){
   $locked=Lead::lockForUpdate()->findOrFail($lead->id);
   abort_unless(Lead::visibleTo($r->user())->whereKey($locked->id)->exists(),404);
   if($data['kind']==='Email' && $locked->version!==(int)$data['version'])throw ValidationException::withMessages(['version'=>'Contact details changed. Refresh before sending email.']);
   if(LeadActivity::withTrashed()->where('request_key',$data['request_key'])->exists())return null;
   $action=$data['kind']==='Call log'?$this->communicationAction($data['call_direction'],$data['subject']??null):(($data['subject']??null) ?: $data['kind']);
   return $locked->activities()->create(['actor'=>$r->user()->name,'actor_id'=>$r->user()->id,'kind'=>$data['kind'],'action'=>$action,'details'=>$data['body'],'request_key'=>$data['request_key'],'email_to'=>$data['kind']==='Email'?$locked->email:null,'delivery_status'=>$data['kind']==='Email'?'Sending':null]);
  });
  if(!$entry)return back()->with('success','This activity was already submitted.');
  if($data['kind']==='Email'){
   $config=$settings->email_config;
   config(['mail.mailers.lead_smtp'=>['transport'=>'smtp','scheme'=>(int)$config['port']===465?'smtps':'smtp','require_tls'=>true,'host'=>$config['host'],'port'=>(int)$config['port'],'username'=>$config['username'],'password'=>$settings->email_password,'timeout'=>20]]);
   try{
    Mail::purge('lead_smtp');
    Mail::mailer('lead_smtp')->send(new \App\Mail\LeadEmail($entry->email_to,$data['subject'],$data['body'],$config['from_address'],$config['from_name']));
    $entry->update(['delivery_status'=>'Sent']);
   }catch(\Throwable $error){
    $entry->update(['delivery_status'=>'Unconfirmed']);
    $reason=$error->getMessage();
    if(preg_match('/\b(?:535|534)\b|authenticat|credential|app.password/i',$reason)){
     $message='SMTP login was rejected. Check the SMTP username and app password in Lead settings. Check your sent mailbox before trying again.';
    }elseif(preg_match('/timed out|timeout|connect|refused|unreachable|resolve|host not found/i',$reason)){
     $message='Could not connect to the SMTP server. Check the host, port and network in Lead settings. Check your sent mailbox before trying again.';
    }elseif(preg_match('/certificate|tls|ssl|crypto/i',$reason)){
     $message='SMTP secure connection failed. Check the selected port and TLS settings in Lead settings. Check your sent mailbox before trying again.';
    }else{
     $message='Email delivery could not be confirmed. Check SMTP settings and your sent mailbox before sending again.';
    }
    return back()->withErrors(['email'=>$message]);
   }
  }
  return redirect()->route('leads.show',$lead)->with('success',$data['kind']==='Email'?'Email accepted by your mail server.':'Activity saved.');
 }
 public function update(Request $r,Lead $lead,LeadActivity $activity){
  $this->checkActivity($r,$lead,$activity);$data=$r->validate(['subject'=>'nullable|string|max:255','body'=>'required|string|max:20000','version'=>'required|integer','call_direction'=>'nullable|in:Incoming,Outgoing,Missed call,Message sent,Message received']);
  if($activity->kind==='Call log' && empty($data['call_direction']))throw ValidationException::withMessages(['call_direction'=>'Choose a call or message direction.']);
  if($activity->kind==='Call log' && mb_strlen(trim($data['subject']??''))>240)throw ValidationException::withMessages(['subject'=>'Call title must be 240 characters or fewer.']);
  if($activity->kind!=='Call log' && !filled($data['subject']??null))throw ValidationException::withMessages(['subject'=>'Enter a title for this activity.']);
  DB::transaction(function()use($r,$lead,$activity,$data){$entry=LeadActivity::lockForUpdate()->findOrFail($activity->id);$this->checkActivity($r,$lead,$entry);if($entry->version!==(int)$data['version'])throw ValidationException::withMessages(['version'=>'Activity changed. Refresh before editing.']);$previous=$entry->action.' · '.$entry->details;$action=$entry->kind==='Call log'?$this->communicationAction($data['call_direction'],$data['subject']??null):$data['subject'];$entry->update(['action'=>$action,'details'=>$data['body'],'version'=>$entry->version+1]);$lead->activities()->create(['actor'=>$r->user()->name,'actor_id'=>$r->user()->id,'kind'=>'System','action'=>'Activity edited','details'=>$previous.' → '.$action.' · '.$data['body']]);});return back()->with('success','Activity updated. Email changes update this record only; they do not resend the email.');
 }
 public function destroy(Request $r,Lead $lead,LeadActivity $activity){
  $this->checkActivity($r,$lead,$activity);$data=$r->validate(['version'=>'required|integer']);
  DB::transaction(function()use($r,$lead,$activity,$data){$entry=LeadActivity::lockForUpdate()->findOrFail($activity->id);$this->checkActivity($r,$lead,$entry);if($entry->version!==(int)$data['version'])throw ValidationException::withMessages(['version'=>'Activity changed. Refresh before deleting.']);$entry->delete();$lead->activities()->create(['actor'=>$r->user()->name,'actor_id'=>$r->user()->id,'kind'=>'System','action'=>'Activity removed','details'=>$entry->action.' · '.$entry->details]);});return back()->with('success','Activity removed from history. This does not recall a sent email.');
 }
 private function checkActivity(Request $r,Lead $lead,LeadActivity $activity):void {abort_unless($activity->lead_id===$lead->id && Lead::visibleTo($r->user())->whereKey($lead->id)->exists(),404);abort_if($activity->kind==='System' || $activity->delivery_status==='Sending',409);}
 private function communicationAction(string $direction,?string $subject):string {
  $base=in_array($direction,['Incoming','Outgoing'],true)?$direction.' call':$direction;
  return $base.(filled($subject)?': '.trim($subject):'');
 }
}
