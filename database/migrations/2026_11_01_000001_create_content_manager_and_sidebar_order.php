<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_item_orders', function (Blueprint $table) {
            $table->id();
            $table->string('item_key', 80)->unique();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('content_documents', function (Blueprint $table) {
            $table->id();
            $table->string('tab', 80)->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('attachment_name', 255);
            $table->string('attachment_path', 500);
            $table->string('attachment_mime', 150)->nullable();
            $table->unsignedBigInteger('attachment_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['tab', 'created_at']);
        });

        $keys = ['dashboard','leads','lead-source-report','pipeline','lead-groups','list-manager','tasks','reminder','meeting','todo','note','lead-work','activities','content-manager','products','product-logs','sizes','add-product','rates','transporters','access','storage','screen-time','chat','notifications','account'];
        $now = now();
        DB::table('sidebar_item_orders')->insert(array_map(fn ($key, $position) => [
            'item_key' => $key, 'position' => $position + 1, 'created_at' => $now, 'updated_at' => $now,
        ], $keys, array_keys($keys)));
    }

    public function down(): void
    {
        Schema::dropIfExists('content_documents');
        Schema::dropIfExists('sidebar_item_orders');
    }
};
