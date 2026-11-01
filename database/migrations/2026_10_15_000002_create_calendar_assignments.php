<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {Schema::create('calendar_assignments',function(Blueprint $table){
  $table->id();$table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();$table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
  $table->string('kind',40);$table->string('title',255);$table->text('detail')->nullable();$table->string('link',500);$table->timestamp('assigned_at')->index();$table->timestamp('due_at')->nullable();$table->index(['recipient_id','assigned_at']);
 });}
 public function down():void {Schema::dropIfExists('calendar_assignments');}
};
