<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overdue_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('overdue_at');
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'user_id']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overdue_alerts');
    }
};
