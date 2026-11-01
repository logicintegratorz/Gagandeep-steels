<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('screen_time_entries', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('screen_time_entries', function (Blueprint $table) {
            $table->dropColumn('last_active_at');
        });
    }
};
