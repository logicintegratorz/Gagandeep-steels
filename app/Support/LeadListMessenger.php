<?php
namespace App\Support;

use App\Mail\LeadEmail;
use App\Models\{Lead,LeadList,LeadListAutomation,LeadListDelivery,LeadSetting,User};
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class LeadListMessenger {
 public static function ready(string $channel,LeadSetting $settings):bool {return match($channel){'Email'=>$settings->emailReady(),'WhatsApp'=>$settings->whatsappReady(),default=>false};}
 public static function send(LeadList $list,Lead $lead,string $channel,?User $actor=null,?LeadListAutomation $automation=null,?LeadListDelivery $delivery=null):LeadListDelivery {
  $settings=LeadSetting::findOrFail(1);
  if(!self::ready($channel,$settings))throw ValidationException::withMessages(['channel'=>$channel.' connection is not configured in Lead settings.']);
  $recipient=$channel==='Email'?$lead->email:LeadWhatsAppLink::number($lead);
  if($channel==='Email'&&!filter_var($recipient,FILTER_VALIDATE_EMAIL))throw ValidationException::withMessages(['recipient'=>$lead->name.' has no valid email address.']);
  if($channel==='WhatsApp'&&!$recipient)throw ValidationException::withMessages(['recipient'=>$lead->name.' has no valid Indian mobile number.']);
  if($delivery){$delivery->update(['recipient'=>$recipient,'status'=>'Sending']);}
  else $delivery=LeadListDelivery::create(['lead_list_id'=>$list->id,'lead_id'=>$lead->id,'automation_id'=>$automation?->id,'sent_by'=>$actor?->id,'channel'=>$channel,'recipient'=>$recipient,'message'=>$list->description,'status'=>'Sending']);
  try{
    if($channel==='Email'){
     $config=$settings->email_config;
     config(['mail.mailers.lead_smtp'=>['transport'=>'smtp','scheme'=>(int)$config['port']===465?'smtps':'smtp','require_tls'=>true,'host'=>$config['host'],'port'=>(int)$config['port'],'username'=>$config['username'],'password'=>$settings->email_password,'timeout'=>20]]);
     Mail::purge('lead_smtp');
     Mail::mailer('lead_smtp')->send(new LeadEmail($recipient,$list->name,$delivery->message,$config['from_address'],$config['from_name']));
     $delivery->update(['status'=>'Accepted']);
    }else{
     $config=$settings->whatsapp_config;
     $response=Http::withToken($settings->whatsapp_token)->acceptJson()->timeout(20)->post('https://graph.facebook.com/'.$config['api_version'].'/'.$config['phone_number_id'].'/messages',[
      'messaging_product'=>'whatsapp','to'=>$recipient,'type'=>'template',
      'template'=>['name'=>$config['template_name'],'language'=>['code'=>$config['language_code']??'en_US'],'components'=>[['type'=>'body','parameters'=>[['type'=>'text','text'=>$delivery->message]]]]],
     ]);
     if(!$response->successful()){
      $error=$response->json('error.message')?:'WhatsApp provider rejected the request.';
      $delivery->update(['status'=>'Failed','error'=>'WhatsApp API: '.mb_substr((string)$error,0,800)]);
      return $delivery->fresh();
     }
     if(!is_string($response->json('messages.0.id')))throw new \RuntimeException('WhatsApp provider did not confirm acceptance.');
     $delivery->update(['status'=>'Accepted','provider_id'=>$response->json('messages.0.id')]);
    }
  }catch(\Throwable $e){
   $authFailed=$channel==='Email' && LeadEmailFailure::authenticationRejected($e->getMessage());
   $delivery->update(['status'=>$authFailed?'Failed':'Unconfirmed','error'=>$channel==='Email'?LeadEmailFailure::message($e->getMessage()):mb_substr($e->getMessage(),0,1000)]);
  }
  return $delivery->fresh();
 }
}
