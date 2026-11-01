<?php

use App\Http\Controllers\{ContentManagerController, SidebarGroupController};
use App\Models\{ContentDocument, SidebarItemOrder, User};
use App\Support\SidebarNavigation;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Schema, Storage, View};
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
View::share('errors', new ViewErrorBag);

$results = [];
$check = function (string $name, bool $passed, mixed $detail = null) use (&$results): void { $results[] = compact('name', 'passed', 'detail'); };
$check('content_documents table', Schema::hasTable('content_documents'));
$check('sidebar_item_orders table', Schema::hasTable('sidebar_item_orders'));
$check('Content Manager navigation definition', isset(SidebarNavigation::definitions()['content-manager']));
$check('all sidebar buttons have saved order', SidebarItemOrder::count() >= count(SidebarNavigation::definitions()), [SidebarItemOrder::count(), count(SidebarNavigation::definitions())]);

$admin = User::where('active', true)->whereHas('role', fn ($q) => $q->where('is_admin', true))->first();
$check('active administrator available', $admin !== null);
if ($admin) {
    Auth::setUser($admin);
    $request = Request::create('/content-manager', 'GET', ['tab' => 'Spectro']);
    $request->setUserResolver(fn () => $admin);
    $html = (new ContentManagerController)->index($request)->render();
    $check('Content Manager renders compact Spectro tab', str_contains($html, 'Spectro documents') && str_contains($html, 'Save document'));
    $check('only drag reorder control renders inside sidebar', str_contains($html, 'sidebar-sort-controls') && str_contains($html, 'data-sidebar-drag-handle') && !str_contains($html, 'Move Dashboard up'));
    $check('admin drag handles render inside sidebar', str_contains($html, 'data-sidebar-drag-handle') && str_contains($html, 'data-sidebar-reorder-url'));

    $regularUser = User::where('active', true)->where('id', '!=', $admin->id)->first();
    if ($regularUser) {
        Auth::setUser($regularUser);
        $regularRequest = Request::create('/content-manager', 'GET', ['tab' => 'Spectro']);
        $regularRequest->setUserResolver(fn () => $regularUser);
        $regularHtml = (new ContentManagerController)->index($regularRequest)->render();
        $check('reorder controls hidden from non-admin users', !str_contains($regularHtml, 'sidebar-sort-controls'));
        Auth::setUser($admin);
    }

    DB::beginTransaction();
    $path = 'content-documents/audit-content-manager.txt';
    try {
        Storage::put($path, 'content manager audit');
        $document = ContentDocument::create(['tab'=>'Spectro','title'=>'Audit spectro document','description'=>'Temporary verification','attachment_name'=>'audit.txt','attachment_path'=>$path,'attachment_mime'=>'text/plain','attachment_size'=>21,'uploaded_by'=>$admin->id]);
        $check('document record saves and file exists', $document->exists && Storage::exists($path));

        $before = array_keys(SidebarNavigation::definitions());
        $index = array_search('content-manager', $before, true);
        if ($index > 0) {
            $move = Request::create('/sidebar-items/content-manager/move', 'POST', ['direction'=>'up'], [], [], ['HTTP_REFERER'=>'/sidebar-groups']);
            $move->setUserResolver(fn () => $admin);
            (new SidebarGroupController)->move($move, 'content-manager');
            $after = array_keys(SidebarNavigation::definitions());
            $check('admin can move sidebar button', array_search('content-manager', $after, true) === $index - 1);
            [$after[0], $after[1]] = [$after[1], $after[0]];
            $reorder = Request::create('/sidebar-items/reorder', 'POST', ['order' => $after]);
            $reorder->setUserResolver(fn () => $admin);
            (new SidebarGroupController)->reorder($reorder);
            $check('drag-drop order endpoint persists complete order', array_keys(SidebarNavigation::definitions()) === $after);
        } else {
            $check('admin can move sidebar button', true, 'Already first');
        }
    } finally {
        DB::rollBack();
        Storage::delete($path);
    }
}

foreach ($results as $result) {
    echo ($result['passed'] ? '[PASS] ' : '[FAIL] ').$result['name'];
    if ($result['detail'] !== null) echo ' — '.json_encode($result['detail']);
    echo PHP_EOL;
}
exit(collect($results)->contains(fn ($result) => !$result['passed']) ? 1 : 0);
