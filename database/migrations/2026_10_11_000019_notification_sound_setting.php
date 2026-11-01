<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void { Schema::create('notification_settings',function(Blueprint $t){$t->unsignedInteger('id')->primary();$t->boolean('sound_enabled')->default(true);}); DB::table('notification_settings')->insert(['id'=>1,'sound_enabled'=>true]); }
 public function down(): void { Schema::dropIfExists('notification_settings'); }
};
