<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('item_code', 80)->unique();
            $table->string('hsn_sac', 20)->nullable();
            $table->string('item_type', 20)->default('Product');
            $table->string('category', 100)->nullable();
            $table->string('unit', 30);
            $table->string('tax_type', 80)->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->string('picture_path')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('company', 150);
            $table->date('effective_date');
            $table->decimal('purchase_price', 14, 3);
            $table->decimal('retail_price', 14, 3)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'company', 'effective_date'], 'product_company_date_unique');
            $table->index(['effective_date', 'product_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
    }
};
