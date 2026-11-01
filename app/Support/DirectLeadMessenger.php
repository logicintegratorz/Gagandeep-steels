<?php

namespace App\Support;

use App\Mail\LeadEmail;
use App\Models\{Lead,LeadActivity,LeadListDelivery,LeadSetting,User};
use Illuminate\Support\Facades\{Http,Mail};

final class DirectLeadMessenger
{
    public static function email(Lead $lead,string $subject,string $message,User $actor):LeadListDelivery
    {
        $settings=LeadSetting::findOrFail(1);
        if(!$settings->emailReady())throw new \RuntimeException('SMTP is not configured.');
        if(!filter_var($lead->email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('No valid email address.');
        $delivery=self::delivery($lead,$actor,'Email',$lead->email,$message);
        $activity=self::activity($lead,$actor,'Email',$subject,$message,$lead->email,'Sending');
        try{
            $config=$settings->email_config;
            config(['mail.mailers.lead_smtp'=>['transport'=>'smtp','scheme'=>(int)$config['port']===465?'smtps':'smtp','require_tls'=>true,'host'=>$config['host'],'port'=>(int)$config['port'],'username'=>$config['username'],'password'=>$settings->email_password,'timeout'=>20]]);
            Mail::purge('lead_smtp');Mail::mailer('lead_smtp')->send(new LeadEmail($lead->email,$subject,$message,$config['from_address'],$config['from_name']));
            $delivery->update(['status'=>'Accepted']);$activity->update(['delivery_status'=>'Sent']);
        }catch(\Throwable $error){$delivery->update(['status'=>'Unconfirmed','error'=>LeadEmailFailure::message($error->getMessage())]);$activity->update(['delivery_status'=>'Unconfirmed']);}
        return $delivery->fresh();
    }

    public static function whatsapp(Lead $lead,string $message,User $actor):LeadListDelivery
    {
        $settings=LeadSetting::findOrFail(1);if(!$settings->whatsappReady())throw new \RuntimeException('WhatsApp Cloud API is not configured.');
        $recipient=LeadWhatsAppLink::number($lead);if(!$recipient)throw new \RuntimeException('No valid WhatsApp number.');
        $delivery=self::delivery($lead,$actor,'WhatsApp',$recipient,$message);$config=$settings->whatsapp_config;
        try{$response=Http::withToken($settings->whatsapp_token)->acceptJson()->timeout(20)->post('https://graph.facebook.com/'.$config['api_version'].'/'.$config['phone_number_id'].'/messages',['messaging_product'=>'whatsapp','to'=>$recipient,'type'=>'template','template'=>['name'=>$config['template_name'],'language'=>['code'=>$config['language_code']??'en_US'],'components'=>[['type'=>'body','parameters'=>[['type'=>'text','text'=>$message]]]]]]);if(!$response->successful())throw new \RuntimeException((string)($response->json('error.message')?:'Provider rejected the message.'));$delivery->update(['status'=>'Accepted','provider_id'=>$response->json('messages.0.id')]);self::activity($lead,$actor,'Call log','Message sent: WhatsApp',$message,null,'Accepted');}
        catch(\Throwable $error){$delivery->update(['status'=>'Failed','error'=>mb_substr($error->getMessage(),0,1000)]);}
        return $delivery->fresh();
    }

    public static function sms(Lead $lead,string $message,User $actor):LeadListDelivery
    {
        $endpoint=config('crm_integrations.sms.endpoint');$token=config('crm_integrations.sms.token');if(!$endpoint||!$token)throw new \RuntimeException('SMS API is not configured.');
        $recipient=preg_replace('/\D+/','',(string)$lead->phone);if(!$recipient)throw new \RuntimeException('No valid phone number.');
        $delivery=self::delivery($lead,$actor,'SMS',$recipient,$message);
        try{$response=Http::withToken($token)->acceptJson()->timeout(20)->post($endpoint,['to'=>$recipient,'message'=>$message,'sender'=>config('crm_integrations.sms.sender'),'lead_id'=>$lead->id]);if(!$response->successful())throw new \RuntimeException((string)($response->json('message')?:'Provider rejected the SMS.'));$delivery->update(['status'=>'Accepted','provider_id'=>$response->json('id')??$response->json('message_id')]);self::activity($lead,$actor,'Call log','Message sent: SMS',$message,null,'Accepted');}
        catch(\Throwable $error){$delivery->update(['status'=>'Failed','error'=>mb_substr($error->getMessage(),0,1000)]);}
        return $delivery->fresh();
    }

    public static function call(Lead $lead,?string $note,User $actor):LeadListDelivery
    {
        $endpoint=config('crm_integrations.calling.endpoint');$token=config('crm_integrations.calling.token');if(!$endpoint||!$token)throw new \RuntimeException('Calling API is not configured.');
        $recipient=preg_replace('/[^\d+]/','',(string)$lead->phone);if(!$recipient)throw new \RuntimeException('No valid phone number.');
        $delivery=self::delivery($lead,$actor,'Call',$recipient,$note??'Outbound call');
        try{$response=Http::withToken($token)->acceptJson()->timeout(20)->post($endpoint,['to'=>$recipient,'caller_id'=>config('crm_integrations.calling.caller_id'),'lead_id'=>$lead->id,'lead_name'=>$lead->name,'note'=>$note]);if(!$response->successful())throw new \RuntimeException((string)($response->json('message')?:'Provider rejected the call request.'));$delivery->update(['status'=>'Accepted','provider_id'=>$response->json('id')??$response->json('call_id')]);self::activity($lead,$actor,'Call log','Outgoing call started via API',$note,null,'Accepted');}
        catch(\Throwable $error){$delivery->update(['status'=>'Failed','error'=>mb_substr($error->getMessage(),0,1000)]);}
        return $delivery->fresh();
    }

    private static function delivery(Lead $lead,User $actor,string $channel,string $recipient,string $message):LeadListDelivery{return LeadListDelivery::create(['lead_id'=>$lead->id,'sent_by'=>$actor->id,'channel'=>$channel,'recipient'=>$recipient,'message'=>$message,'status'=>'Sending']);}
    private static function activity(Lead $lead,User $actor,string $kind,string $action,string $details,?string $email=null,?string $status=null):LeadActivity{return $lead->activities()->create(['actor'=>$actor->name,'actor_id'=>$actor->id,'kind'=>$kind,'action'=>$action,'details'=>$details,'email_to'=>$email,'delivery_status'=>$status]);}
}
