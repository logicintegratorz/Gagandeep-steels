<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::create('lead_visits',function(Blueprint $table){
   $table->id();$table->foreignId('lead_id')->constrained()->cascadeOnDelete();$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
   $table->uuid('session_key');$table->timestamp('opened_at');$table->timestamp('last_seen_at');$table->timestamp('closed_at')->nullable();$table->timestamp('stage_updated_at')->nullable();$table->timestamps();
   $table->unique(['lead_id','session_key']);$table->index(['lead_id','id']);
  });
 }
 public function down():void {Schema::dropIfExists('lead_visits');}
};
