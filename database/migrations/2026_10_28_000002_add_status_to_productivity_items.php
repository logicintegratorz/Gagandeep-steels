<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productivity_items', function (Blueprint $table) {
            $table->string('status', 80)->default('New')->index()->after('module');
        });
    }

    public function down(): void
    {
        Schema::table('productivity_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
