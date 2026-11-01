<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // These schedules were created when WhatsApp only produced manual-send reminders.
        DB::table('lead_list_automations')->where('channel', 'WhatsApp')->where('active', true)
            ->update(['active' => false, 'next_run_at' => null, 'updated_at' => now()]);
    }

    public function down(): void {}
};
