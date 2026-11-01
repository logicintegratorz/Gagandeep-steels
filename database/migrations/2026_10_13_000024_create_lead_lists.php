<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::create('lead_lists',function(Blueprint $t){$t->id();$t->foreignId('created_by')->constrained('users');$t->string('name',150);$t->text('description');$t->timestamps();});
  Schema::create('lead_list_members',function(Blueprint $t){$t->id();$t->foreignId('lead_list_id')->constrained('lead_lists')->cascadeOnDelete();$t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();$t->timestamps();$t->unique(['lead_list_id','lead_id']);});
  Schema::create('lead_list_automations',function(Blueprint $t){$t->id();$t->foreignId('lead_list_id')->constrained('lead_lists')->cascadeOnDelete();$t->string('channel',20);$t->string('frequency',20);$t->time('send_time');$t->unsignedTinyInteger('day_of_week')->nullable();$t->unsignedTinyInteger('day_of_month')->nullable();$t->dateTime('next_run_at')->nullable();$t->boolean('active')->default(false);$t->timestamps();});
  Schema::create('lead_list_deliveries',function(Blueprint $t){$t->id();$t->foreignId('lead_list_id')->nullable()->constrained('lead_lists')->nullOnDelete();$t->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();$t->foreignId('automation_id')->nullable()->constrained('lead_list_automations')->nullOnDelete();$t->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();$t->string('channel',20);$t->string('recipient',255);$t->text('message');$t->string('status',30);$t->string('provider_id',255)->nullable();$t->text('error')->nullable();$t->timestamps();$t->index(['lead_list_id','created_at']);});
  Schema::table('lead_settings',function(Blueprint $t){$t->json('whatsapp_config')->nullable();$t->text('whatsapp_token')->nullable();});
 }
 public function down():void {Schema::table('lead_settings',fn(Blueprint $t)=>$t->dropColumn(['whatsapp_config','whatsapp_token']));Schema::dropIfExists('lead_list_deliveries');Schema::dropIfExists('lead_list_automations');Schema::dropIfExists('lead_list_members');Schema::dropIfExists('lead_lists');}
};
