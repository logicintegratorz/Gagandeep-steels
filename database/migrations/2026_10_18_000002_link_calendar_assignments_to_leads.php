<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::table('calendar_assignments',function(Blueprint $table){
   $table->foreignId('lead_id')->nullable()->after('recipient_id')->constrained('leads')->nullOnDelete();
   $table->index(['lead_id','assigned_at']);
  });
 }
 public function down():void {
  Schema::table('calendar_assignments',function(Blueprint $table){
   $table->dropIndex(['lead_id','assigned_at']);
   $table->dropConstrainedForeignId('lead_id');
  });
 }
};
