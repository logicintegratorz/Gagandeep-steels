<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('read_at');
            $table->softDeletes();
        });
        Schema::table('chat_group_messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('body');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', fn (Blueprint $table) => $table->dropColumn(['edited_at','deleted_at']));
        Schema::table('chat_group_messages', fn (Blueprint $table) => $table->dropColumn(['edited_at','deleted_at']));
    }
};
