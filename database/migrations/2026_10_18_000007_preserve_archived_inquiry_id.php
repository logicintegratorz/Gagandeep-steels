<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::table('leads',function(Blueprint $table){$table->string('archived_external_id',150)->nullable()->after('external_id');});
  DB::table('leads')->whereNotNull('deleted_at')->whereNotNull('external_id')->orderBy('id')->chunkById(100,function($leads){
   foreach($leads as $lead)DB::table('leads')->where('id',$lead->id)->update(['archived_external_id'=>$lead->external_id,'external_id'=>null]);
  });
 }
 public function down():void {Schema::table('leads',fn(Blueprint $table)=>$table->dropColumn('archived_external_id'));}
};
