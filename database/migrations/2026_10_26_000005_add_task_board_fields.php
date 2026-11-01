<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('lead_work_items',function(Blueprint $t){$t->string('label')->nullable()->after('priority');$t->text('comments')->nullable()->after('notes');}); }
 public function down(): void { Schema::table('lead_work_items',fn(Blueprint $t)=>$t->dropColumn(['label','comments'])); }
};
