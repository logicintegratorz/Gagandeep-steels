<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_price_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('product_name', 150);
            $table->string('item_code', 80);
            $table->string('unit', 30);
            $table->string('company', 150)->index();
            $table->date('effective_date')->index();
            $table->text('old_purchase_price')->nullable();
            $table->text('old_retail_price')->nullable();
            $table->text('purchase_price')->nullable();
            $table->text('retail_price')->nullable();
            $table->string('event', 30);
            $table->string('changed_by')->nullable();
            $table->timestamp('changed_at')->index();
        });
        DB::table('product_prices')->join('products', 'products.id', '=', 'product_prices.product_id')
            ->select('product_prices.*', 'products.name as product_name', 'products.item_code', 'products.unit')
            ->orderBy('product_prices.id')->chunk(200, function ($rows) {
                foreach ($rows as $row) DB::table('product_price_logs')->insert([
                    'product_id'=>$row->product_id, 'product_name'=>$row->product_name, 'item_code'=>$row->item_code, 'unit'=>$row->unit,
                    'company'=>$row->company, 'effective_date'=>$row->effective_date,
                    'purchase_price'=>$row->purchase_price, 'retail_price'=>$row->retail_price,
                    'event'=>'Existing snapshot', 'changed_by'=>null, 'changed_at'=>$row->updated_at,
                ]);
            });
    }
    public function down(): void { Schema::dropIfExists('product_price_logs'); }
};
