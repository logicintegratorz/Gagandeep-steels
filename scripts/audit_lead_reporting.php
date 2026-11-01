<?php

use App\Http\Controllers\{AccessController, AuthController, LeadSourceReportController};
use App\Models\{Lead, LeadBackupSetting, LeadSourceEvent, User};
use App\Support\LeadTenure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema, View};
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
View::share('errors', new ViewErrorBag);

$checks = [];
$check = function (string $name, bool $passed, mixed $detail = null) use (&$checks): void {
    $checks[] = compact('name', 'passed', 'detail');
};

$check('users.phone column', Schema::hasColumn('users', 'phone'));
$check('users.avatar_path column', Schema::hasColumn('users', 'avatar_path'));
$check('leads.alt_phone column', Schema::hasColumn('leads', 'alt_phone'));
$check('lead_source_events table', Schema::hasTable('lead_source_events'));
$check('lead_backups table', Schema::hasTable('lead_backups'));
$check('backup settings row', LeadBackupSetting::find(1) !== null);
$check('public avatar storage link', is_dir(public_path('storage')));

$sourceRows = LeadSourceEvent::query()
    ->selectRaw('source, COUNT(*) total, SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) deleted, SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) lost')
    ->groupBy('source')
    ->get();
$check('source aggregation query', true, $sourceRows->count().' source(s)');

$admin = User::where('active', true)->whereHas('role', fn ($q) => $q->where('is_admin', true))->first();
if ($admin) {
    auth()->setUser($admin);
    $dashboardRequest = Request::create('/', 'GET');
    $dashboardRequest->setUserResolver(fn () => $admin);
    $dashboardHtml = (new AuthController)->home($dashboardRequest)->render();
    $check('source report removed from dashboard', !str_contains($dashboardHtml, 'Permanent received, lost and deleted counts'));
    $check('dashboard total leads and opened labels', str_contains($dashboardHtml, '>Total leads<') && str_contains($dashboardHtml, ' opened<'));

    $leadFormHtml = view('leads.form', [
        'lead' => new Lead(['stage' => 'New', 'priority' => 'Normal']),
        'owners' => collect(),
        'jobRoles' => \App\Models\JobRole::orderBy('name')->pluck('name'),
    ])->render();
    $phonePosition = strpos($leadFormHtml, 'name="phone"');
    $altPosition = strpos($leadFormHtml, 'name="alt_phone"');
    $check('Alt number follows Phone field', $phonePosition !== false && $altPosition !== false && $altPosition > $phonePosition);

    $reportRequest = Request::create('/lead-source-report', 'GET');
    $reportRequest->setUserResolver(fn () => $admin);
    $reportHtml = (new LeadSourceReportController)($reportRequest)->render();
    $check('dedicated source report renders', str_contains($reportHtml, 'Incoming leads by source') && str_contains($reportHtml, 'Latest deleted'));

    $accessRequest = Request::create('/users-permissions', 'GET');
    $accessRequest->setUserResolver(fn () => $admin);
    $accessHtml = (new AccessController)->index($accessRequest)->render();
    $check('user image and phone form renders', str_contains($accessHtml, 'User image (optional)') && str_contains($accessHtml, 'Phone number'));

    $request = Request::create('/users', 'POST', [
        'name' => 'Audit User', 'email' => '', 'phone' => '+91 98888-12345',
        'active' => 1, 'role_id' => $admin->role_id,
        'password' => 'AuditPass123', 'password_confirmation' => 'AuditPass123',
    ]);
    $request->setUserResolver(fn () => $admin);
    $method = new ReflectionMethod(AccessController::class, 'userData');
    $data = $method->invoke(new AccessController, $request, null);
    $check('phone-only user validation', $data['phone'] === '919888812345', $data['phone']);
} else {
    $check('phone-only user validation', false, 'No active administrator available');
}

DB::beginTransaction();
try {
    $lead = Lead::query()->first();
    if ($lead) {
        $event = LeadSourceEvent::firstOrCreate(
            ['lead_id' => $lead->id],
            ['source' => $lead->source ?: 'Manual', 'received_at' => $lead->created_at]
        );
        $originalStage = $lead->stage;
        $lead->alt_phone = '9876543210';
        $lead->save();
        $check('Alt number persists', $lead->fresh()->alt_phone === '9876543210');
        $lead->stage = 'Lost';
        $lead->save();
        $check('Lost status tracking', $event->fresh()->lost_at !== null);
        $lead->delete();
        $check('Deleted status tracking', $event->fresh()->deleted_at !== null);
        $lead->stage = $originalStage;
    } else {
        $check('Lost status tracking', true, 'Skipped: no leads');
        $check('Deleted status tracking', true, 'Skipped: no leads');
    }
    $result = LeadTenure::run();
    $check('lead tenure job', isset($result['created'], $result['purged']), $result);
} finally {
    DB::rollBack();
}

foreach ($checks as $result) {
    echo ($result['passed'] ? '[PASS] ' : '[FAIL] ').$result['name'];
    if ($result['detail'] !== null) echo ' — '.json_encode($result['detail']);
    echo PHP_EOL;
}

exit(collect($checks)->contains(fn ($result) => !$result['passed']) ? 1 : 0);
