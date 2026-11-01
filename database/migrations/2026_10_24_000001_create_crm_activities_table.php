<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('name', 255);
            $table->string('activity_type', 80)->default('General');
            $table->string('status', 30)->default('Scheduled');
            $table->timestamp('scheduled_at');
            $table->text('description')->nullable();
            $table->string('link', 2048)->nullable();
            $table->boolean('send_calendar_invite')->default(false);
            $table->text('completion_comment')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['created_by', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
