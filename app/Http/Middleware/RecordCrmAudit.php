<?php
namespace App\Http\Middleware;

use App\Models\CrmAuditEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RecordCrmAudit {
 public function handle(Request $request,Closure $next):Response {
  $response=$next($request);
  $name=$request->route()?->getName();
  $coveredByLeadHistory=['leads.visit','leads.contact','leads.activities.store','leads.store','leads.transfer','leads.update','leads.assign','leads.stage','leads.work.store','leads.work.update','leads.work.status'];
  if(!$request->user() || !in_array($request->method(),['POST','PUT','PATCH','DELETE'],true) || $response->getStatusCode()>=400 || !$name || Str::startsWith($name,['dashboard.','session.','screen-time.']) || in_array($name,$coveredByLeadHistory,true))return $response;
  $referrer=$request->headers->get('referer');
  $link=route('home');
  if($referrer && parse_url($referrer,PHP_URL_HOST)===$request->getHost()){
   $path=parse_url($referrer,PHP_URL_PATH);
   if(is_string($path) && str_starts_with($path,'/'))$link=url($path);
  }
  try{CrmAuditEvent::create(['user_id'=>$request->user()->id,'action'=>Str::headline(str_replace('.',' ',$name)),'route_name'=>$name,'method'=>$request->method(),'link'=>$link,'occurred_at'=>now()]);}
  catch(\Throwable $error){report($error);}
  return $response;
 }
}
