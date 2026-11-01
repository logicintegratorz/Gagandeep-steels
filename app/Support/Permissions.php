<?php
namespace App\Support;

final class Permissions
{
    public static function groups(): array
    {
        return [
            'Lead management' => ['leads.view'=>'Open assigned leads and pipeline', 'leads.view_all'=>'View all leads', 'leads.create'=>'Create leads', 'leads.edit'=>'Edit lead contact details', 'leads.delete'=>'Delete leads', 'leads.stage'=>'Change lead stage', 'leads.assign'=>'Transfer any visible lead (owners can transfer their own)', 'leads.followups'=>'Update assigned follow-ups', 'leads.tasks'=>'Update assigned tasks', 'leads.reminders'=>'Update assigned reminders', 'leads.activity'=>'Add, edit and remove lead activities', 'leads.email'=>'Send emails to leads (also needs activity access)', 'leads.lists'=>'Create lists and manage their contacts', 'leads.broadcast'=>'Send list messages', 'leads.automations'=>'Set list message schedules'],
            'Products & pricing' => ['products.view' => 'View products & dated prices', 'products.create' => 'Add products & prices', 'products.edit' => 'Edit products / prices / status'],
            'Freight rates' => ['rates.view' => 'View & search', 'rates.create' => 'Add rates', 'rates.edit' => 'Edit rates', 'rates.delete' => 'Delete rates', 'rates.export' => 'Export CSV'],
            'Transporters' => ['transporters.view' => 'View & search', 'transporters.create' => 'Add transporters', 'transporters.edit' => 'Edit transporters', 'transporters.delete' => 'Delete transporters'],
            'Users' => ['users.view' => 'View users', 'users.create' => 'Create users', 'users.edit' => 'Edit / activate / deactivate', 'users.delete' => 'Delete users', 'users.assign_roles' => 'Assign roles'],
            'Roles & permissions' => ['roles.view' => 'View roles', 'roles.create' => 'Create roles', 'roles.edit' => 'Edit roles & permissions', 'roles.delete' => 'Delete unused roles'],
            'Notifications' => ['notifications.view' => 'View sent notification history', 'notifications.send' => 'Compose & send notifications', 'notifications.recall' => 'Recall sent notifications'],
        ];
    }
    public static function keys(): array { return array_keys(array_merge(...array_values(self::groups()))); }

    public static function expand(array $permissions): array
    {
        $permissions = array_values(array_unique($permissions));
        foreach (['leads.email' => 'leads.activity', 'leads.automations' => 'leads.broadcast', 'users.create' => 'users.assign_roles'] as $permission => $required) {
            if (in_array($permission, $permissions, true)) $permissions[] = $required;
        }
        foreach ($permissions as $permission) {
            $view = explode('.', $permission, 2)[0].'.view';
            if (in_array($view, self::keys(), true)) $permissions[] = $view;
        }
        return array_values(array_unique($permissions));
    }
}
