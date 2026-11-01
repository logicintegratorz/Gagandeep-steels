<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('crm_activities', function (Blueprint $table) {
            $table->boolean('send_calendar_to_assignee')->default(false)->after('send_calendar_invite');
            $table->boolean('send_calendar_to_client')->default(false)->after('send_calendar_to_assignee');
        });
    }

    public function down(): void
    {
        Schema::table('crm_activities', function (Blueprint $table) {
            $table->dropColumn(['send_calendar_to_assignee', 'send_calendar_to_client']);
        });
    }
};
