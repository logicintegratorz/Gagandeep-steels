<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['chat_group_id', 'user_id']);
        });
        Schema::create('chat_group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['chat_group_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_group_messages');
        Schema::dropIfExists('chat_group_members');
        Schema::dropIfExists('chat_groups');
    }
};
