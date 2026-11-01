<?php
namespace App\Support;

use App\Models\Lead;

final class LeadIdentity {
 public static function keys(?string $name,?string $company,?string $phone,?string $location):array {
  $digits=preg_replace('/\D+/','',(string)$phone);
  $phoneKey=strlen($digits)>=7?(strlen($digits)>10?substr($digits,-10):$digits):null;
  $normalize=fn(?string $value)=>trim(preg_replace('/\s+/u',' ',mb_strtolower((string)$value)));
  $parts=array_map($normalize,[$name,$company,$location]);
  $profile=in_array('',$parts,true)?null:hash('sha256',implode('|',$parts));
  return [$phoneKey,$profile];
 }
 public static function attachPrevious(Lead $lead):void {
  if(!$lead->identity_phone && !$lead->identity_profile)return;
  $matches=Lead::withTrashed()->where('id','<',$lead->id)->where(function($query)use($lead){
   if($lead->identity_phone)$query->where('identity_phone',$lead->identity_phone);
   if($lead->identity_profile)$query->{$lead->identity_phone?'orWhere':'where'}('identity_profile',$lead->identity_profile);
  })->where('created_at','<=',$lead->created_at)->get(['id','identity_phone','identity_profile']);
  foreach($matches as $previous){
   $lead->repeatMatches()->firstOrCreate(['previous_lead_id'=>$previous->id],['reason'=>$lead->identity_phone && $previous->identity_phone===$lead->identity_phone?'Phone':'Name, company and location']);
  }
 }
}
