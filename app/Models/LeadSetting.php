<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadSetting extends Model {
 protected $guarded=['id'];
 protected $hidden=['email_password','whatsapp_token'];
 protected function casts():array{return ['weights'=>'array','email_config'=>'array','email_password'=>'encrypted','whatsapp_config'=>'array','whatsapp_token'=>'encrypted','version'=>'integer'];}
 const FIELDS=['name'=>'Name','company'=>'Company name','created_by'=>'Added by','website'=>'Website','email'=>'Email','whatsapp'=>'WhatsApp number','phone'=>'Phone number','alt_phone'=>'Alt number','location'=>'Location','source'=>'Source','category'=>'Category'];
 public function score(Lead $lead):int {
  $score=0;
  foreach(self::FIELDS as $field=>$label){
   if(filled($lead->$field))$score+=max(0,(int)($this->weights[$field]??0));
  }
  return min(100,$score);
 }
 public function emailReady():bool{return filled($this->email_config['host']??null) && filled($this->email_config['from_address']??null) && filled($this->email_password);}
 public function whatsappReady():bool{return filled($this->whatsapp_config['phone_number_id']??null) && filled($this->whatsapp_config['api_version']??null) && filled($this->whatsapp_config['template_name']??null) && filled($this->whatsapp_token);}
}
