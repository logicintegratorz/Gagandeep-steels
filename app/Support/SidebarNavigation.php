<?php

namespace App\Support;

use App\Models\User;
use App\Models\SidebarItemOrder;
use Illuminate\Support\Facades\Schema;

final class SidebarNavigation
{
    public static function definitions(): array
    {
        $definitions = [
            'dashboard' => ['label' => 'Dashboard', 'icon' => 'box'],
            'leads' => ['label' => 'Leads', 'icon' => 'users'],
            'lead-source-report' => ['label' => 'Lead source report', 'icon' => 'task-list'],
            'pipeline' => ['label' => 'Pipeline', 'icon' => 'box'],
            'lead-groups' => ['label' => 'Lead Lists', 'icon' => 'menu'],
            'list-manager' => ['label' => 'List Manager', 'icon' => 'menu'],
            'tasks' => ['label' => 'Task', 'icon' => 'task-list'],
            'reminder' => ['label' => 'Reminder', 'icon' => 'bell'],
            'meeting' => ['label' => 'Meeting', 'icon' => 'users'],
            'todo' => ['label' => 'To-Do', 'icon' => 'check'],
            'note' => ['label' => 'Note', 'icon' => 'edit'],
            'lead-work' => ['label' => 'Follow-ups, Tasks & Reminders', 'icon' => 'check'],
            'activities' => ['label' => 'Activities', 'icon' => 'task-list'],
            'content-manager' => ['label' => 'Content Manager', 'icon' => 'menu'],
            'products' => ['label' => 'Products & pricing', 'icon' => 'box'],
            'product-logs' => ['label' => 'Global log sheet', 'icon' => 'menu'],
            'sizes' => ['label' => 'All sizes', 'icon' => 'box'],
            'add-product' => ['label' => 'Add product', 'icon' => 'plus'],
            'rates' => ['label' => 'Freight rates', 'icon' => 'route'],
            'transporters' => ['label' => 'Transporters', 'icon' => 'truck'],
            'access' => ['label' => 'Users & Permissions', 'icon' => 'users'],
            'storage' => ['label' => 'Storage overview', 'icon' => 'box'],
            'screen-time' => ['label' => 'Screen time report', 'icon' => 'task-list'],
            'chat' => ['label' => 'Team chat', 'icon' => 'chat'],
            'notifications' => ['label' => 'Notifications', 'icon' => 'bell'],
            'account' => ['label' => 'My account', 'icon' => 'lock'],
        ];
        if (!Schema::hasTable('sidebar_item_orders')) return $definitions;
        $positions = SidebarItemOrder::pluck('position', 'item_key');
        $fallback = array_flip(array_keys($definitions));
        uksort($definitions, fn ($a, $b) => ($positions[$a] ?? 1000 + $fallback[$a]) <=> ($positions[$b] ?? 1000 + $fallback[$b]));
        return $definitions;
    }

    public static function visible(User $user): array
    {
        $leads = $user->allows('leads.view');
        $definitions = self::definitions();
        $runtime = [
            'dashboard' => [true, route('home'), request()->routeIs('home')],
            'leads' => [$leads, route('leads.index'), request()->routeIs('leads.index','leads.create','leads.show','leads.edit')],
            'lead-source-report' => [$leads, route('leads.source-report'), request()->routeIs('leads.source-report')],
            'pipeline' => [$leads, route('leads.pipeline'), request()->routeIs('leads.pipeline')],
            'lead-groups' => [$leads, route('lead-groups.index'), request()->routeIs('lead-groups.*')],
            'list-manager' => [$leads && $user->allows('leads.lists'), route('leads.lists'), request()->routeIs('leads.lists*')],
            'tasks' => [$leads && $user->allows('leads.tasks'), route('tasks.index'), request()->routeIs('tasks.index')],
            'reminder' => [$leads, route('productivity.index','reminders'), request()->is('productivity/reminders')],
            'meeting' => [$leads, route('productivity.index','meetings'), request()->is('productivity/meetings')],
            'todo' => [$leads, route('productivity.index','to-dos'), request()->is('productivity/to-dos')],
            'note' => [$leads, route('productivity.index','notes'), request()->is('productivity/notes')],
            'lead-work' => [$leads && ($user->allows('leads.tasks') || $user->allows('leads.followups') || $user->allows('leads.reminders')), route('leads.work'), request()->routeIs('leads.work')],
            'activities' => [true, route('activities.index'), request()->routeIs('activities.*')],
            'content-manager' => [true, route('content-manager.index'), request()->routeIs('content-manager.*')],
            'products' => [$user->allows('products.view') || $user->allows('products.create') || $user->allows('products.edit'), route('products.index'), request()->routeIs('products.*') && !request()->routeIs('products.logs')],
            'product-logs' => [$user->allows('products.view'), route('products.logs'), request()->routeIs('products.logs')],
            'sizes' => [$user->allows('products.view'), route('sizes.index'), request()->routeIs('sizes.index')],
            'add-product' => [$user->allows('products.create'), route('products.create'), request()->routeIs('products.create')],
            'rates' => [$user->allows('rates.view'), route('rates.index'), request()->routeIs('rates.*')],
            'transporters' => [$user->allows('transporters.view'), route('transporters.index'), request()->routeIs('transporters.*')],
            'access' => [$user->allows('users.view') || $user->allows('roles.view'), route('access.index'), request()->routeIs('access.*','users.*','roles.*')],
            'storage' => [$user->isAdmin(), route('storage.index'), request()->routeIs('storage.*')],
            'screen-time' => [$user->isAdmin(), route('screen-time.index'), request()->routeIs('screen-time.*')],
            'chat' => [true, route('chat.index'), request()->routeIs('chat.*')],
            'notifications' => [true, route('notifications.index'), request()->routeIs('notifications.*','broadcasts.*')],
            'account' => [true, route('account.index'), request()->routeIs('account.*')],
        ];

        $items = [];
        foreach ($definitions as $key => $definition) {
            [$visible, $url, $active] = $runtime[$key];
            if (!$visible) continue;
            $items[$key] = $definition + ['key' => $key, 'url' => $url, 'active' => $active];
        }
        return $items;
    }
}
