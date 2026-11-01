<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {Schema::create('crm_audit_events',function(Blueprint $table){
  $table->id();$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
  $table->string('action',150);$table->string('route_name',150)->nullable();$table->string('method',10);$table->string('link',500);
  $table->timestamp('occurred_at')->index();$table->index(['user_id','occurred_at']);
 });}
 public function down():void {Schema::dropIfExists('crm_audit_events');}
};
