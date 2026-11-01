<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lead_work_items', function (Blueprint $table) {
            $table->boolean('recurring')->default(false)->after('priority');
            $table->string('recurrence_type', 20)->nullable()->after('recurring');
            $table->string('attachment_path')->nullable()->after('notes');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
        });
    }

    public function down(): void
    {
        Schema::table('lead_work_items', function (Blueprint $table) {
            $table->dropColumn(['recurring', 'recurrence_type', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size']);
        });
    }
};
