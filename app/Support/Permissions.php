<?php
namespace App\Support;

final class Permissions
{
    public static function groups(): array
    {
        return [
            'Lead management' => ['leads.view'=>'Open assigned leads and pipeline', 'leads.view_all'=>'View all leads', 'leads.contacts'=>'View manual contacts', 'leads.create'=>'Create manual contacts', 'leads.edit'=>'Edit lead and contact details', 'leads.delete'=>'Delete leads and contacts', 'leads.stage'=>'Change lead stage', 'leads.assign'=>'Transfer any visible lead (owners can transfer their own)', 'leads.followups'=>'Update assigned follow-ups', 'leads.tasks'=>'Update assigned tasks', 'leads.reminders'=>'Update assigned reminders', 'leads.activity'=>'Add, edit and remove lead activities', 'leads.email'=>'Send emails to leads (also needs activity access)', 'leads.lists'=>'Create lists and manage their contacts', 'leads.broadcast'=>'Send list messages', 'leads.automations'=>'Set list message schedules'],
            'Products & pricing' => ['products.view' => 'View products & dated prices', 'products.create' => 'Add products & prices', 'products.edit' => 'Edit products / prices / status'],
            'Freight rates' => ['rates.view' => 'View & search', 'rates.create' => 'Add rates', 'rates.edit' => 'Edit rates', 'rates.delete' => 'Delete rates', 'rates.export' => 'Export CSV'],
            'Transporters' => ['transporters.view' => 'View & search', 'transporters.create' => 'Add transporters', 'transporters.edit' => 'Edit transporters', 'transporters.delete' => 'Delete transporters'],
            'Users' => ['users.view' => 'View users', 'users.create' => 'Create users', 'users.edit' => 'Edit / activate / deactivate', 'users.delete' => 'Delete users', 'users.assign_roles' => 'Assign roles'],
            'Roles & permissions' => ['roles.view' => 'View roles', 'roles.create' => 'Create roles', 'roles.edit' => 'Edit roles & permissions', 'roles.delete' => 'Delete unused roles'],
            'Notifications' => ['notifications.view' => 'View sent notification history', 'notifications.send' => 'Compose & send notifications', 'notifications.recall' => 'Recall sent notifications'],
            'Productivity — Reminder' => self::productivityPermissions('reminders', 'Reminder'),
            'Productivity — Meeting' => self::productivityPermissions('meetings', 'Meeting'),
            'Productivity — To-Do' => self::productivityPermissions('to_dos', 'To-Do'),
            'Productivity — Note' => self::productivityPermissions('notes', 'Note'),
            'Workspace' => ['workspace.activities.view' => 'View CRM activities', 'workspace.activities.manage' => 'Create, edit and delete CRM activities', 'workspace.chat' => 'Use team chat and groups'],
            'Content manager' => ['content.view' => 'View and download team documents', 'content.manage' => 'Add, edit and delete team documents'],
            'Reporting & administration' => ['reports.storage' => 'View storage overview', 'reports.screen_time' => 'View screen-time report'],
        ];
    }
    public static function keys(): array { return array_keys(array_merge(...array_values(self::groups()))); }

    public static function expand(array $permissions): array
    {
        $permissions = array_values(array_unique($permissions));
        foreach (['leads.email' => 'leads.activity', 'leads.automations' => 'leads.broadcast', 'users.create' => 'users.assign_roles', 'workspace.activities.manage' => 'workspace.activities.view', 'content.manage' => 'content.view'] as $permission => $required) {
            if (in_array($permission, $permissions, true)) $permissions[] = $required;
        }
        foreach (['reminders', 'meetings', 'to_dos', 'notes'] as $module) {
            foreach (['create', 'edit', 'delete'] as $action) {
                if (in_array("productivity.$module.$action", $permissions, true)) $permissions[] = "productivity.$module.view";
            }
        }
        foreach ($permissions as $permission) {
            $view = explode('.', $permission, 2)[0].'.view';
            if (in_array($view, self::keys(), true)) $permissions[] = $view;
        }
        return array_values(array_unique($permissions));
    }

    private static function productivityPermissions(string $module, string $label): array
    {
        return [
            "productivity.$module.view" => "View $label tab",
            "productivity.$module.create" => "Add $label records",
            "productivity.$module.edit" => "Edit and move $label records",
            "productivity.$module.delete" => "Delete $label records",
        ];
    }
}
