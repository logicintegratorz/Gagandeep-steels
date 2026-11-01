<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::table('lead_activities',function(Blueprint $table){
   $table->foreignId('actor_id')->nullable()->after('actor')->constrained('users')->nullOnDelete();
  });
 }
 public function down():void {
  Schema::table('lead_activities',fn(Blueprint $table)=>$table->dropConstrainedForeignId('actor_id'));
 }
};
