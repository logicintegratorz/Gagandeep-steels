<?php

use App\Models\{CrmNotification, LeadConnection, LeadListAutomation, LeadListDelivery, LeadSetting, OverdueAlert, ProductivityItem, User};
use App\Support\{LeadListMessenger, OverdueAlerts};
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\{DB, Schema};

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$results = [];
$check = function (string $name, bool $passed, mixed $detail = null) use (&$results): void {
    $results[] = compact('name', 'passed', 'detail');
};

$check('overdue alert table', Schema::hasTable('overdue_alerts'));
$admins = User::where('active', true)->whereHas('role', fn ($q) => $q->where('is_admin', true))->pluck('id');
$assignee = User::where('active', true)->whereNotIn('id', $admins)->first() ?: User::where('active', true)->first();
$admin = User::whereIn('id', $admins)->first();
$check('active administrator available', $admin !== null);
$check('active assignee available', $assignee !== null);

$settings = LeadSetting::find(1);
$activeAutomations = LeadListAutomation::with(['list.creator'])->where('active', true)->get();
$invalidAutomations = $activeAutomations->filter(fn ($automation) => !$automation->list?->active
    || !$automation->list?->creator?->active
    || !$automation->list?->creator?->allows('leads.broadcast')
    || !$settings
    || !LeadListMessenger::ready($automation->channel, $settings));
$check('active list automations are runnable', $invalidAutomations->isEmpty(), [
    'active' => $activeAutomations->count(), 'needs_attention' => $invalidAutomations->count(),
]);
$check('no delivery stuck in Sending for 15 minutes', !LeadListDelivery::where('status', 'Sending')->where('updated_at', '<', now()->subMinutes(15))->exists());
$check('enabled lead connections identified', true, LeadConnection::where('enabled', true)->count());
$check('productivity customer automations identified', true, ProductivityItem::where('email_automation', true)->orWhere('whatsapp_automation', true)->count());

if ($admin && $assignee) {
    DB::beginTransaction();
    try {
        $item = ProductivityItem::create([
            'module' => 'To-Do', 'status' => 'New', 'created_by' => $admin->id,
            'assigned_to' => $assignee->id, 'title' => 'Automation audit overdue item',
            'scheduled_at' => now()->subMinutes(5), 'recurrence' => 'Once',
        ]);
        $item->assignees()->sync([$assignee->id]);
        $expected = collect([$assignee->id])->merge($admins)->unique()->count();
        $first = OverdueAlerts::deliver(OverdueAlerts::PRODUCTIVITY, $item->id);
        $second = OverdueAlerts::deliver(OverdueAlerts::PRODUCTIVITY, $item->id);
        $markers = OverdueAlert::where('source_type', OverdueAlerts::PRODUCTIVITY)->where('source_id', $item->id)->count();
        $notifications = CrmNotification::whereIn('user_id', collect([$assignee->id])->merge($admins)->unique())
            ->where('title', 'Overdue To-Do: Automation audit overdue item')->count();
        $check('assignee and admin alerts delivered', $first === $expected && $notifications === $expected, compact('first', 'expected', 'notifications'));
        $check('duplicate alerts prevented', $second === 0 && $markers === $expected, compact('second', 'markers'));
    } finally {
        DB::rollBack();
    }
}

foreach ($results as $result) {
    echo ($result['passed'] ? '[PASS] ' : '[FAIL] ').$result['name'];
    if ($result['detail'] !== null) echo ' — '.json_encode($result['detail']);
    echo PHP_EOL;
}
exit(collect($results)->contains(fn ($result) => !$result['passed']) ? 1 : 0);
