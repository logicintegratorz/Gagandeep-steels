<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::table('leads',fn(Blueprint $t)=>$t->timestamp('contact_saved_at')->nullable());
  DB::table('leads')->whereNull('connection_id')->update(['contact_saved_at'=>now()]);
  Schema::table('lead_work_items',fn(Blueprint $t)=>$t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete());
  DB::table('lead_work_items')->orderBy('id')->chunkById(200,function($items){
   $owners=DB::table('leads')->whereIn('id',$items->pluck('lead_id'))->pluck('owner_id','id');
   foreach($items as $item)DB::table('lead_work_items')->where('id',$item->id)->update(['assigned_to'=>$owners[$item->lead_id]??null]);
  });
  Schema::table('lead_activities',function(Blueprint $t){$t->softDeletes();$t->unsignedInteger('version')->default(1);});
 }
 public function down():void {Schema::table('lead_activities',function(Blueprint $t){$t->dropSoftDeletes();$t->dropColumn('version');});Schema::table('lead_work_items',fn(Blueprint $t)=>$t->dropConstrainedForeignId('assigned_to'));Schema::table('leads',fn(Blueprint $t)=>$t->dropColumn('contact_saved_at'));}
};
