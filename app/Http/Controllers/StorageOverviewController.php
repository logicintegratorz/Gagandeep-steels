<?php

namespace App\Http\Controllers;

use App\Models\DirectMessage;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadList;
use App\Models\LeadWorkItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class StorageOverviewController
{
    private const QUOTA_BYTES = 2_048 * 1_024 * 1_024;

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $storedFiles = collect(Storage::disk('local')->allFiles());
        $usedBytes = $storedFiles->sum(fn (string $path) => Storage::disk('local')->size($path));
        $productImagePaths = Product::query()->whereNotNull('picture_path')->pluck('picture_path');
        $productImageBytes = $productImagePaths->sum(fn (string $path) => Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : 0);
        $total = self::QUOTA_BYTES;
        $used = min($usedBytes, $total);
        $percent = $total ? min(100, round(($used / $total) * 100, 1)) : 0;

        $modules = [
            ['label' => 'Leads', 'count' => Lead::count(), 'icon' => 'users', 'url' => route('leads.index')],
            ['label' => 'Tasks', 'count' => LeadWorkItem::where('kind', 'Task')->count(), 'icon' => 'task-list', 'url' => route('leads.work', ['kind' => 'Task'])],
            ['label' => 'Reminders', 'count' => LeadWorkItem::where('kind', 'Reminder')->count(), 'icon' => 'bell', 'url' => route('leads.work', ['kind' => 'Reminder'])],
            ['label' => 'Follow-ups', 'count' => LeadWorkItem::where('kind', 'Follow-up')->count(), 'icon' => 'check', 'url' => route('leads.work', ['kind' => 'Follow-up'])],
            ['label' => 'Chats', 'count' => DirectMessage::count(), 'icon' => 'chat', 'url' => route('chat.index')],
            ['label' => 'Contacts', 'count' => Lead::whereNotNull('contact_saved_at')->count(), 'icon' => 'users', 'url' => route('home').'#contacts-table'],
            ['label' => 'Call logs', 'count' => LeadActivity::where('kind', 'Call log')->count(), 'icon' => 'call-log', 'url' => route('leads.index')],
            ['label' => 'Lead lists', 'count' => LeadList::count(), 'icon' => 'menu', 'url' => route('lead-groups.index')],
            ['label' => 'Product images', 'count' => $productImagePaths->count(), 'icon' => 'document', 'bytes' => $productImageBytes, 'url' => route('products.index')],
            ['label' => 'Archived leads', 'count' => Lead::onlyTrashed()->count(), 'icon' => 'trash', 'url' => route('leads.archived')],
        ];

        return view('storage.index', compact('total', 'used', 'percent', 'modules', 'storedFiles'));
    }
}
