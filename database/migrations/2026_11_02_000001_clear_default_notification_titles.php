<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('notification_broadcasts')->where('title', 'Notification')->update(['title' => '']);
        DB::table('crm_notifications')->where('title', 'Notification')->update(['title' => '']);
    }

    public function down(): void
    {
        // Blank titles are intentional and cannot be reliably distinguished from prior data.
    }
};
