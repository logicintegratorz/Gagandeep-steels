<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('productivity_items',function(Blueprint $table){$table->text('customer_email')->nullable()->change();$table->text('customer_mobile')->nullable()->change();}); }
 public function down(): void { Schema::table('productivity_items',function(Blueprint $table){$table->string('customer_email')->nullable()->change();$table->string('customer_mobile',30)->nullable()->change();}); }
};
