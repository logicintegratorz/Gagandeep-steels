<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('lead_list_deliveries')->where('channel', 'WhatsApp')->where('status', 'Pending')
            ->update(['status' => 'Skipped', 'error' => 'Created before automatic WhatsApp sending was enabled.', 'updated_at' => now()]);
    }

    public function down(): void {}
};
