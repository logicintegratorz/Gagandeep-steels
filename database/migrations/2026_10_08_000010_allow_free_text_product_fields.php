<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasIndex('products', 'products_item_code_unique')) $table->dropUnique('products_item_code_unique');
            foreach (['name','item_code','hsn_sac','category','tax_type'] as $column) $table->text($column)->nullable()->change();
        });
        if (!Schema::hasIndex('product_prices', 'product_prices_product_id_index')) Schema::table('product_prices', fn (Blueprint $table) => $table->index('product_id'));
        Schema::table('product_prices', function (Blueprint $table) {
            if (Schema::hasIndex('product_prices', 'product_company_date_unique')) $table->dropUnique('product_company_date_unique');
            $table->text('company')->change();
            $table->text('purchase_price')->nullable()->change();
            $table->text('retail_price')->nullable()->change();
        });
        Schema::table('product_price_logs', function (Blueprint $table) {
            if (Schema::hasIndex('product_price_logs', 'product_price_logs_company_index')) $table->dropIndex(['company']);
            foreach (['product_name','item_code'] as $column) $table->text($column)->nullable()->change();
            $table->text('company')->change();
        });
    }
    public function down(): void
    {
        // Free-text values cannot safely be converted back to decimal prices.
        throw new RuntimeException('Restore a backup to revert free-text product fields without losing data.');
    }
};
