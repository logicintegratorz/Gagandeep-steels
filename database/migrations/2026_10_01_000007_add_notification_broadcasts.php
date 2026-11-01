<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('submission_key')->unique();
            $table->string('title',120);
            $table->text('message');
            $table->string('audience',20);
            $table->unsignedInteger('recipient_count');
            $table->timestamp('recalled_at')->nullable();
            $table->timestamps();
        });
        Schema::table('crm_notifications', function (Blueprint $table) {
            $table->foreignId('broadcast_id')->nullable()->constrained('notification_broadcasts')->cascadeOnDelete();
            $table->timestamp('dismissed_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('crm_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('broadcast_id');
            $table->dropColumn('dismissed_at');
        });
        Schema::dropIfExists('notification_broadcasts');
    }
};
