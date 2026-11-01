<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
 public function up():void {
  foreach(DB::table('lead_settings')->whereNotNull('weights')->get(['id','weights']) as $setting){
   $weights=json_decode($setting->weights,true);
   if(!is_array($weights))continue;
   $weights=array_map(fn($value)=>max(0,min(100,(int)$value)),$weights);
   $total=array_sum($weights);
   if($total<=100)continue;
   $scaled=[];$fractions=[];$assigned=0;
   foreach($weights as $field=>$value){$exact=$value*100/$total;$scaled[$field]=(int)floor($exact);$fractions[$field]=$exact-$scaled[$field];$assigned+=$scaled[$field];}
   arsort($fractions);
   foreach(array_keys($fractions) as $field){if($assigned>=100)break;$scaled[$field]++;$assigned++;}
   DB::table('lead_settings')->where('id',$setting->id)->update(['weights'=>json_encode($scaled),'version'=>DB::raw('version + 1'),'updated_at'=>now()]);
  }
 }
 public function down():void {}
};
