<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('product_size_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('size_id')->index();
            $table->text('size_name')->nullable();
            $table->string('activity');
            $table->text('actor_name');
            $table->json('before_values');
            $table->json('after_values')->nullable();
            $table->timestamp('occurred_at');
        });
    }
    public function down(): void { Schema::dropIfExists('product_size_activities'); }
};
