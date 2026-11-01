<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {Schema::table('leads',fn(Blueprint $table)=>$table->string('job_role',100)->nullable()->index());}
 public function down():void {Schema::table('leads',function(Blueprint $table){$table->dropIndex(['job_role']);$table->dropColumn('job_role');});}
};
