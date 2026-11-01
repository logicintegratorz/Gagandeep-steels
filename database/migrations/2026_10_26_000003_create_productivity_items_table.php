<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('productivity_items',function(Blueprint $t){$t->id();$t->string('module',30)->index();$t->foreignId('created_by')->constrained('users')->cascadeOnDelete();$t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();$t->string('title');$t->text('message')->nullable();$t->timestamp('scheduled_at')->nullable()->index();$t->string('recurrence',30)->default('Once');$t->string('priority',20)->nullable();$t->boolean('carry_forward')->default(false);$t->boolean('send_instant')->default(false);$t->string('template_type',30)->nullable();$t->string('template_name')->nullable();$t->boolean('email_automation')->default(false);$t->boolean('whatsapp_automation')->default(false);$t->string('customer_email')->nullable();$t->string('customer_mobile',30)->nullable();$t->string('attachment_name')->nullable();$t->string('attachment_path')->nullable();$t->string('attachment_mime')->nullable();$t->unsignedBigInteger('attachment_size')->nullable();$t->timestamps();}); }
 public function down(): void { Schema::dropIfExists('productivity_items'); }
};
