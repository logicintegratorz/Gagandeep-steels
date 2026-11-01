<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('freight_rates', function (Blueprint $table) {
            $table->id();
            $table->string('origin', 100);
            $table->string('destination', 100);
            $table->decimal('price', 11, 2);
            $table->string('transporter', 150);
            $table->text('remarks')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['updated_at', 'id']);
            $table->index('transporter');
        });
    }
    public function down(): void { Schema::dropIfExists('freight_rates'); }
};
