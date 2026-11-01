<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('lead_connections',function(Blueprint $t){$t->id();$t->string('provider')->unique();$t->boolean('enabled')->default(false);$t->text('credentials')->nullable();$t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('last_attempt_at')->nullable();$t->timestamp('synced_until')->nullable();$t->timestamp('last_success_at')->nullable();$t->string('status')->default('Not configured');$t->timestamps();});
  foreach(['IndiaMART','Justdial','TradeIndia'] as $provider)DB::table('lead_connections')->insert(['provider'=>$provider,'created_at'=>now(),'updated_at'=>now()]);
  Schema::table('leads',function(Blueprint $t){$t->foreignId('connection_id')->nullable()->constrained('lead_connections')->nullOnDelete();$t->string('external_id',150)->nullable();$t->unique(['connection_id','external_id']);});
 }
 public function down():void {Schema::table('leads',function(Blueprint $t){$t->dropUnique(['connection_id','external_id']);$t->dropConstrainedForeignId('connection_id');$t->dropColumn('external_id');});Schema::dropIfExists('lead_connections');}
};
