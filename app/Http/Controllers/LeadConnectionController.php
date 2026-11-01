<?php
namespace App\Http\Controllers;
use App\Models\{CalendarAssignment,LeadConnection,User};
use App\Support\{LeadSync,LeadImport};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Validator};
final class LeadConnectionController {
 public function index(Request $r){abort_unless($r->user()->isAdmin(),403);return view('leads.connections',['connections'=>LeadConnection::all(),'owners'=>User::with('role')->where('active',true)->get()->filter(fn($u)=>$u->allows('leads.view'))]);}
 public function save(Request $r,LeadConnection $connection){
  abort_unless($r->user()->isAdmin(),403);
  $data=$r->validate(['enabled'=>'nullable|boolean','owner_id'=>'required|integer|exists:users,id','api_key'=>'nullable|string|max:2000','user_id'=>'nullable|string|max:150','profile_id'=>'nullable|string|max:150']);
  if(!User::findOrFail($data['owner_id'])->allows('leads.view'))return back()->withErrors(['owner_id'=>'Select an active owner with lead access.']);
  DB::transaction(function()use($connection,$data,$r){
   $item=LeadConnection::lockForUpdate()->findOrFail($connection->id);$credentials=$item->credentials??[];
   if(filled($data['api_key']??null))$credentials['api_key']=$data['api_key'];
   foreach(['user_id','profile_id'] as $field)if(filled($data[$field]??null))$credentials[$field]=$data[$field];
   if($item->provider==='Justdial' && empty($credentials['webhook_secret']))$credentials['webhook_secret']=bin2hex(random_bytes(32));
   if($r->boolean('enabled') && $item->provider!=='Justdial' && empty($credentials['api_key']))throw \Illuminate\Validation\ValidationException::withMessages(['api_key'=>'Enter the provider API key.']);
   if($r->boolean('enabled') && $item->provider==='TradeIndia' && (empty($credentials['user_id']) || empty($credentials['profile_id'])))throw \Illuminate\Validation\ValidationException::withMessages(['user_id'=>'TradeIndia requires User ID and Profile ID.']);
   $ownerChanged=$item->owner_id!==(int)$data['owner_id'];
   $item->fill(['credentials'=>$credentials,'owner_id'=>$data['owner_id'],'enabled'=>$r->boolean('enabled'),'status'=>$r->boolean('enabled')?($item->provider==='Justdial'?'Awaiting first webhook':'Saved; awaiting sync'):'Disabled'])->save();
   if($ownerChanged)CalendarAssignment::create(['actor_id'=>$r->user()->id,'recipient_id'=>$item->owner_id,'kind'=>'Lead source','title'=>$item->provider.' connection manager changed','detail'=>'New leads from '.$item->provider.' appear unassigned until a user opens one.','link'=>route('leads.index'),'assigned_at'=>now()]);
  });
  LeadSync::run($connection->fresh());
  return back()->with('success','Connection settings saved. Check the status below.');
 }
 public function sync(Request $r,LeadConnection $connection){abort_unless($r->user()->isAdmin(),403);LeadSync::run($connection);return back()->with('success','Sync checked. Provider requests are limited to one every 15 minutes.');}
 public function webhook(Request $r,LeadConnection $connection){
  abort_unless($connection->provider==='Justdial' && $connection->enabled,404);
  $expected=$connection->credentials['webhook_secret']??'';$provided=$r->header('X-Lead-Key',$r->query('key',''));
  abort_unless(is_string($provided) && $expected && hash_equals($expected,$provided),401);
  abort_if(strlen($r->getContent())>100000,413);
  try{$created=LeadImport::ingest($connection,$r->except('key'));$connection->update(['last_success_at'=>now(),'status'=>'Connected']);return response()->json(['accepted'=>true,'created'=>$created]);}
  catch(\Throwable $e){$connection->update(['status'=>'Webhook rejected. Check payload fields and default owner.']);return response()->json(['accepted'=>false,'message'=>'Valid inquiry ID and contact details required; default owner must have lead access.'],422);}
 }
}
