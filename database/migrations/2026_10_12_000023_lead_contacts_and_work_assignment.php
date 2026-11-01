<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::table('leads',fn(Blueprint $t)=>$t->timestamp('contact_saved_at')->nullable());
  DB::table('leads')->whereNull('connection_id')->update(['contact_saved_at'=>now()]);
  Schema::table('lead_work_items',fn(Blueprint $t)=>$t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete());
  DB::statement('UPDATE lead_work_items w JOIN leads l ON w.lead_id=l.id SET w.assigned_to=l.owner_id');
  Schema::table('lead_activities',function(Blueprint $t){$t->softDeletes();$t->unsignedInteger('version')->default(1);});
 }
 public function down():void {Schema::table('lead_activities',function(Blueprint $t){$t->dropSoftDeletes();$t->dropColumn('version');});Schema::table('lead_work_items',fn(Blueprint $t)=>$t->dropConstrainedForeignId('assigned_to'));Schema::table('leads',fn(Blueprint $t)=>$t->dropColumn('contact_saved_at'));}
};
