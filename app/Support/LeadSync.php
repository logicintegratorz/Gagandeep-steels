<?php
namespace App\Support;
use App\Models\LeadConnection;
use Illuminate\Support\Facades\{Cache,Http};
final class LeadSync {
 public static function run(LeadConnection $connection):void {
  $connection->refresh();
  if(!$connection->enabled || $connection->provider==='Justdial')return;
  $lock=Cache::lock('lead-sync-'.$connection->id,90);if(!$lock->get())return;
  try{
   $connection->refresh();
   if($connection->last_attempt_at && $connection->last_attempt_at->gt(now()->subMinutes(15)))return;
   $connection->update(['last_attempt_at'=>now(),'status'=>'Checking connection']);
   $key=$connection->credentials['api_key']??null;if(!$key)throw new \RuntimeException();
   $start=$connection->synced_until?->copy()->subMinutes(2)??now()->startOfDay();
   $end=$start->copy()->addDay()->min(now());
   $http=Http::acceptJson()->timeout(25)->connectTimeout(10)->withoutRedirecting();
   if($connection->provider==='IndiaMART'){
    $response=$http->get('https://mapi.indiamart.com/wservce/crm/crmListing/v2/',['glusr_crm_key'=>$key,'start_time'=>$start->format('d-M-Y H:i:s'),'end_time'=>$end->format('d-M-Y H:i:s')]);
    if(!$response->successful())throw new \RuntimeException();
    $payload=$response->json();if(!is_array($payload) || (int)($payload['CODE']??0)!==200 || !is_array($payload['RESPONSE']??null))throw new \RuntimeException();
    $rows=$payload['RESPONSE'];
    if(count($rows)>=200)throw new \RuntimeException('capacity');
   }else{
    $response=$http->get('https://www.tradeindia.com/utils/my_inquiry.html',['key'=>$key,'userid'=>$connection->credentials['user_id']??'','profile_id'=>$connection->credentials['profile_id']??'','from_date'=>$start->format('Y-m-d'),'to_date'=>$end->format('Y-m-d')]);
    if(!$response->successful())throw new \RuntimeException();
    $payload=$response->json();$rows=is_array($payload) && array_is_list($payload)?$payload:($payload['RESPONSE']??$payload['data']??null);
    if(!is_array($rows) || !array_is_list($rows))throw new \RuntimeException();
   }
   foreach($rows as $row){if(!is_array($row))throw new \RuntimeException();LeadImport::ingest($connection,$row);}
   $connection->update(['synced_until'=>$end,'last_success_at'=>now(),'status'=>'Connected']);
  }catch(\Throwable $error){$connection->update(['status'=>$error->getMessage()==='capacity'?'Result limit reached; historical import needs a smaller time window.':'Sync unsuccessful. Check credentials, account access and provider response format.']);}
  finally{$lock->release();}
 }
}
