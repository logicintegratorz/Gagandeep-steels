<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_settings', function (Blueprint $table) {
            $table->unsignedInteger('same_stage_alert_days')->default(3);
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('stage_changed_at')->nullable()->index();
        });
        DB::table('leads')->whereNull('stage_changed_at')->update([
            'stage_changed_at' => DB::raw('COALESCE(updated_at, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $table) => $table->dropColumn('stage_changed_at'));
        Schema::table('lead_settings', fn (Blueprint $table) => $table->dropColumn('same_stage_alert_days'));
    }
};
