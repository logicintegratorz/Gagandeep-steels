<?php

use App\Models\{CrmNotification, Lead, LeadSetting, OverdueAlert, User};
use App\Support\StagnantLeadAlerts;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [];
DB::beginTransaction();
try {
    $admin = User::where('active', true)->whereHas('role', fn ($q) => $q->where('is_admin', true))->firstOrFail();
    $owner = User::where('active', true)->whereKeyNot($admin->id)->first() ?: $admin;
    $settings = LeadSetting::findOrFail(1);
    $settings->same_stage_alert_days = 3;
    $settings->saveQuietly();
    $lead = Lead::create(['name' => 'Same-stage alert audit', 'stage' => 'New', 'priority' => 'Normal', 'source' => 'Audit', 'owner_id' => $owner->id, 'created_by' => $admin->id, 'version' => 1]);
    DB::table('leads')->where('id', $lead->id)->update(['stage_changed_at' => now()->subDays(4)]);

    $first = StagnantLeadAlerts::deliver();
    $recipientIds = collect([$owner->id, $admin->id])->unique();
    $checks['owner_and_admin_notified'] = CrmNotification::whereIn('user_id', $recipientIds)->where('title', 'like', '%Same-stage alert audit%')->count() === $recipientIds->count();
    $checks['one_marker_per_recipient'] = OverdueAlert::where('source_type', StagnantLeadAlerts::TYPE)->where('source_id', $lead->id)->count() === $recipientIds->count();
    $checks['duplicate_suppressed'] = StagnantLeadAlerts::deliver() === 0;

    $lead->refresh();
    $lead->stage = 'Initial Contact';
    $lead->save();
    $checks['stage_change_resets_timer'] = $lead->stage_changed_at->greaterThan(now()->subMinute())
        && !OverdueAlert::where('source_type', StagnantLeadAlerts::TYPE)->where('source_id', $lead->id)->exists();
    DB::table('leads')->where('id', $lead->id)->update(['stage' => 'Won', 'stage_changed_at' => now()->subDays(4)]);
    $checks['won_and_lost_excluded'] = StagnantLeadAlerts::deliver() === 0;
    $checks['delivery_count_includes_recipients'] = $first >= $recipientIds->count();
} finally {
    DB::rollBack();
}

foreach ($checks as $name => $passed) echo ($passed ? 'PASS' : 'FAIL').' '.$name.PHP_EOL;
exit(in_array(false, $checks, true) ? 1 : 0);
