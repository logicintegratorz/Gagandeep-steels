<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('product_sizes', function (Blueprint $table) { $table->text('manufacturer_price')->nullable(); $table->text('godown_price')->nullable(); }); }
    public function down(): void { Schema::table('product_sizes', fn (Blueprint $table) => $table->dropColumn(['manufacturer_price','godown_price'])); }
};
