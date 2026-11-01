<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::table('leads',function(Blueprint $t){$t->string('website',500)->nullable();$t->string('whatsapp',60)->nullable();});
  Schema::table('lead_activities',function(Blueprint $t){$t->string('kind',30)->default('System')->index();$t->string('email_to')->nullable();$t->string('delivery_status',30)->nullable();$t->uuid('request_key')->nullable()->unique();});
  Schema::create('lead_settings',function(Blueprint $t){$t->id();$t->json('weights')->nullable();$t->json('email_config')->nullable();$t->text('email_password')->nullable();$t->unsignedInteger('version')->default(1);$t->timestamps();});
  DB::table('lead_settings')->insert(['id'=>1,'created_at'=>now(),'updated_at'=>now()]);
 }
 public function down():void {Schema::dropIfExists('lead_settings');Schema::table('lead_activities',function(Blueprint $t){$t->dropUnique(['request_key']);$t->dropColumn(['kind','email_to','delivery_status','request_key']);});Schema::table('leads',fn(Blueprint $t)=>$t->dropColumn(['website','whatsapp']));}
};
