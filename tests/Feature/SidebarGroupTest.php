<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SidebarGroup;
use App\Models\SidebarGroupItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SidebarGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_manage_sidebar_groups(): void
    {
        $admin = $this->signIn();
        $this->get(route('sidebar-groups.index'))->assertOk()->assertSee('Create group');

        $role = Role::create(['name' => 'Basic', 'description' => 'Basic user', 'permissions' => []]);
        $user = User::create(['name' => 'Basic User', 'email' => 'basic@example.test', 'password' => 'Password123', 'role_id' => $role->id, 'active' => true]);
        $this->signIn($user);
        $this->get(route('sidebar-groups.index'))->assertForbidden();
        $this->post(route('sidebar-groups.store'), ['name' => 'Blocked'])->assertForbidden();
    }

    public function test_admin_can_create_rename_assign_move_and_delete_groups(): void
    {
        $this->signIn();
        $this->post(route('sidebar-groups.store'), ['name' => 'Sales', 'items' => ['dashboard', 'leads']])->assertSessionHasNoErrors();
        $sales = SidebarGroup::where('name', 'Sales')->firstOrFail();
        $this->assertDatabaseHas('sidebar_group_items', ['sidebar_group_id' => $sales->id, 'item_key' => 'leads']);

        $this->post(route('sidebar-groups.store'), ['name' => 'Office', 'items' => ['account']])->assertSessionHasNoErrors();
        $office = SidebarGroup::where('name', 'Office')->firstOrFail();
        $this->put(route('sidebar-groups.update', $office), [
            'name' => 'Administration', 'version' => $office->version, 'items' => ['account', 'leads'],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sidebar_groups', ['id' => $office->id, 'name' => 'Administration']);
        $this->assertDatabaseHas('sidebar_group_items', ['sidebar_group_id' => $office->id, 'item_key' => 'leads']);
        $this->assertDatabaseMissing('sidebar_group_items', ['sidebar_group_id' => $sales->id, 'item_key' => 'leads']);

        $office->refresh();
        $this->delete(route('sidebar-groups.destroy', $office), ['version' => $office->version])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sidebar_groups', ['id' => $office->id]);
        $this->assertDatabaseMissing('sidebar_group_items', ['sidebar_group_id' => $office->id]);
    }

    public function test_grouped_sidebar_still_respects_user_permissions(): void
    {
        $admin = $this->signIn();
        $group = SidebarGroup::create(['name' => 'Daily work']);
        SidebarGroupItem::create(['sidebar_group_id' => $group->id, 'item_key' => 'dashboard', 'position' => 0]);
        SidebarGroupItem::create(['sidebar_group_id' => $group->id, 'item_key' => 'leads', 'position' => 1]);
        SidebarGroupItem::create(['sidebar_group_id' => $group->id, 'item_key' => 'account', 'position' => 2]);

        $role = Role::create(['name' => 'No leads', 'description' => 'No lead access', 'permissions' => []]);
        $user = User::create(['name' => 'Restricted User', 'email' => 'restricted@example.test', 'password' => 'Password123', 'role_id' => $role->id, 'active' => true]);
        $this->signIn($user);
        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee('Daily work')
            ->assertSee('Dashboard')
            ->assertSee('My account')
            ->assertDontSee('>Leads<', false)
            ->assertDontSee('Sidebar groups');
    }
}
