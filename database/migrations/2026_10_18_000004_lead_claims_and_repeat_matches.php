<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::table('leads',function(Blueprint $table){
   $table->foreignId('claimed_by')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
   $table->timestamp('claimed_at')->nullable()->after('claimed_by');
   $table->string('identity_phone',64)->nullable()->index();
   $table->string('identity_profile',64)->nullable()->index();
  });
  Schema::create('lead_repeat_matches',function(Blueprint $table){
   $table->id();
   $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
   $table->foreignId('previous_lead_id')->constrained('leads')->cascadeOnDelete();
   $table->string('reason',30);
   $table->timestamps();
   $table->unique(['lead_id','previous_lead_id']);
  });
  DB::table('leads')->orderBy('id')->chunkById(200,function($leads){
   foreach($leads as $lead){
    [$phone,$profile]=\App\Support\LeadIdentity::keys($lead->name,$lead->company,$lead->phone,$lead->location);
    DB::table('leads')->where('id',$lead->id)->update(['identity_phone'=>$phone,'identity_profile'=>$profile]);
   }
  });
  DB::table('lead_activities')->where('action','Lead assigned')->where(fn($query)=>$query->where('details','like','%claimed on first open%')->orWhere('details','Claimed when opened'))->orderBy('id')->chunkById(200,function($activities){
   foreach($activities as $activity){
    DB::table('leads')->where('id',$activity->lead_id)->whereNull('claimed_at')->update(['claimed_by'=>$activity->actor_id,'claimed_at'=>$activity->created_at]);
   }
  });
 }
 public function down():void {
  Schema::dropIfExists('lead_repeat_matches');
  Schema::table('leads',function(Blueprint $table){
   $table->dropIndex(['identity_phone']);$table->dropIndex(['identity_profile']);
   $table->dropConstrainedForeignId('claimed_by');
   $table->dropColumn(['claimed_at','identity_phone','identity_profile']);
  });
 }
};
