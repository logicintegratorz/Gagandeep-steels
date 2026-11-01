<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('sidebar_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidebar_group_id')->constrained()->cascadeOnDelete();
            $table->string('item_key', 80)->unique();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_group_items');
        Schema::dropIfExists('sidebar_groups');
    }
};
