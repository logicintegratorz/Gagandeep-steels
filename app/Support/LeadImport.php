<?php
namespace App\Support;
use App\Models\{Lead,LeadConnection};
use Illuminate\Support\Facades\DB;
final class LeadImport {
 public static function ingest(LeadConnection $connection,array $row):bool {
  $row=array_change_key_case($row,CASE_LOWER);
  $pick=function(array $keys,int $limit=255)use($row){foreach($keys as $key)if(isset($row[$key]) && is_scalar($row[$key]) && trim((string)$row[$key])!=='')return mb_substr(trim((string)$row[$key]),0,$limit);return null;};
  $id=$pick(['unique_query_id','rfi_id','query_id','leadid','lead_id','id'],150);
  if(!$id)throw new \RuntimeException('Provider record has no unique inquiry ID.');
  $name=$pick(['sender_name','name','contact_name','contact_person']);
  $phone=$pick(['sender_mobile','sender_mobile_no','mobile','phone','mobile_no'],60);
  $email=$pick(['sender_email','email']);
  if(!$name && !$phone && !$email)throw new \RuntimeException('Provider record has no contact details.');
  return DB::transaction(function()use($connection,$id,$name,$phone,$email,$pick){
   $locked=LeadConnection::with('owner.role')->lockForUpdate()->findOrFail($connection->id);
   if(!$locked->enabled || !$locked->owner?->allows('leads.view'))throw new \RuntimeException('Select an active lead owner.');
   if(Lead::where('connection_id',$locked->id)->where('external_id',$id)->exists())return false;
   $leadName=$name?:($phone?:$email);$company=$pick(['sender_company','company','company_name','sender_company_name']);$location=$pick(['sender_city','city','location','sender_address','address']);
   [$phoneKey,$profileKey]=LeadIdentity::keys($leadName,$company,$phone,$location);
   $lead=Lead::create(['connection_id'=>$locked->id,'external_id'=>$id,'name'=>$leadName,'phone'=>$phone,'email'=>filter_var($email,FILTER_VALIDATE_EMAIL)?$email:null,'company'=>$company,'location'=>$location,'identity_phone'=>$phoneKey,'identity_profile'=>$profileKey,'category'=>$pick(['query_product_name','product_name','category','category_name'],100),'notes'=>$pick(['query_message','message','description','query'],20000),'source'=>$locked->provider,'owner_id'=>null,'stage'=>'New','priority'=>'Normal']);
   LeadIdentity::attachPrevious($lead);
   $lead->activities()->create(['actor'=>$locked->provider,'action'=>'Lead imported','kind'=>'System','details'=>'Inquiry '.$id.' received from '.$locked->provider]);return true;
  });
 }
}
