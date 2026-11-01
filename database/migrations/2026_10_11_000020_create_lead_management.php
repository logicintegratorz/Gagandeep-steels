<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('leads',function(Blueprint $t){
   $t->id(); $t->string('name'); $t->string('company')->nullable(); $t->string('phone',60)->nullable(); $t->string('email')->nullable(); $t->string('location')->nullable(); $t->string('source',100)->nullable(); $t->string('category',100)->nullable();
   $t->string('stage',40)->default('New')->index(); $t->string('priority',20)->default('Normal'); $t->decimal('value',15,2)->nullable(); $t->text('notes')->nullable();
   $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->unsignedInteger('version')->default(1); $t->timestamps();
  });
  Schema::create('lead_activities',function(Blueprint $t){$t->id();$t->foreignId('lead_id')->constrained()->cascadeOnDelete();$t->string('actor');$t->string('action');$t->text('details')->nullable();$t->timestamps();});
  Schema::create('lead_work_items',function(Blueprint $t){
   $t->id();$t->foreignId('lead_id')->constrained()->cascadeOnDelete();$t->string('kind',20);$t->string('title');$t->text('notes')->nullable();$t->dateTime('due_at')->index();$t->string('priority',20)->default('Normal');$t->string('status',20)->default('Pending');$t->text('outcome')->nullable();$t->dateTime('completed_at')->nullable();$t->dateTime('notified_at')->nullable();$t->unsignedInteger('version')->default(1);$t->timestamps();
  });
 }
 public function down(): void {Schema::dropIfExists('lead_work_items');Schema::dropIfExists('lead_activities');Schema::dropIfExists('leads');}
};
