<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productivity_item_user', function (Blueprint $table) {
            $table->foreignId('productivity_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['productivity_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productivity_item_user');
    }
};
