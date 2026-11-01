<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Role};

final class AccessControlTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge(['name'=>'New User','email'=>'new@example.test','password'=>'SecurePassword123','password_confirmation'=>'SecurePassword123','active'=>1,'role_id'=>Role::where('name','Viewer')->firstOrFail()->id],$overrides);
    }
    public function test_viewer_cannot_mutate_export_or_manage_access(): void
    {
        $viewer = User::create($this->payload()); $this->signIn($viewer);
        $this->get(route('rates.index'))->assertOk()->assertDontSee('data-save-form',false)->assertDontSee('Export CSV');
        $this->get(route('transporters.index'))->assertOk()->assertDontSee('data-save-form',false);
        foreach (['rates.store','transporters.store','users.store','roles.store'] as $route) $this->post(route($route),[])->assertForbidden();
        $this->get(route('rates.export'))->assertForbidden(); $this->get(route('access.index'))->assertForbidden();
        $this->get(route('users.edit',$viewer))->assertForbidden();
    }
    public function test_lead_view_only_role_sees_only_its_available_dashboard_actions(): void
    {
        $role = Role::create(['name' => 'Lead viewer', 'permissions' => ['leads.view']]);
        $user = User::create($this->payload(['email' => 'leadviewer@example.test', 'role_id' => $role->id]));
        $this->signIn($user);

        $this->get(route('home'))->assertOk()
            ->assertSee('Total Leads')->assertSee('Opportunities')
            ->assertDontSee('Call Details')->assertDontSee('Quotation')
            ->assertDontSee('SMS sent')->assertDontSee('Total Tasks')
            ->assertDontSee('List Manager')->assertDontSee('Add New Lead');
        $this->get(route('products.index'))->assertForbidden();
        $this->get(route('rates.index'))->assertForbidden();
        $this->get(route('leads.lists'))->assertForbidden();
        $this->get(route('leads.work'))->assertForbidden();
    }
    public function test_administrator_keeps_the_call_details_dashboard_card(): void
    {
        $admin = $this->signIn();
        $lead = \App\Models\Lead::create(['name' => 'Call buyer', 'owner_id' => $admin->id]);
        $lead->activities()->create(['actor' => $admin->name, 'kind' => 'Call log', 'action' => 'Outgoing call', 'details' => 'Quote sent']);
        $lead->activities()->create(['actor' => $admin->name, 'kind' => 'Call log', 'action' => 'Incoming call', 'details' => 'Buyer called']);
        $this->get(route('home'))->assertOk()->assertViewHas('callCounts', [
            'Outgoing Calls' => 1, 'Incoming Calls' => 1,
        ])->assertSee('Call Details')->assertSee('Outgoing Calls')->assertSee('Incoming Calls');
    }
    public function test_admin_can_create_update_deactivate_and_delete_users(): void
    {
        $this->signIn(); $this->get(route('access.index'))->assertOk()->assertSee('Roles &amp; permissions',false);
        $this->post(route('users.store'),$this->payload())->assertSessionHasNoErrors();
        $user = User::where('email','new@example.test')->firstOrFail();
        $this->get(route('users.edit',$user))->assertOk()->assertSee('Save user');
        $data = ['name'=>'Updated User','email'=>$user->email,'active'=>0,'role_id'=>Role::where('name','Operator')->firstOrFail()->id,'version'=>1];
        $this->put(route('users.update',$user),$data)->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->active); $this->assertSame(2,$user->fresh()->session_version);
        $this->put(route('users.update',$user),$data)->assertSessionHasErrors('conflict');
        $this->delete(route('users.destroy',$user),['version'=>2])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users',['id'=>$user->id]);
    }
    public function test_protected_admin_and_used_roles_cannot_be_removed(): void
    {
        $admin = $this->signIn();
        $this->delete(route('users.destroy',$admin),['version'=>1])->assertForbidden();
        $this->put(route('users.update',$admin),['name'=>$admin->name,'email'=>$admin->email,'active'=>0,'role_id'=>$admin->role_id,'version'=>1])->assertSessionHasErrors('active');
        $this->put(route('roles.update',$admin->role),['name'=>'Changed','version'=>1])->assertForbidden();
        $this->delete(route('roles.destroy',$admin->role),['version'=>1])->assertForbidden();
        $viewer = User::create($this->payload());
        $this->delete(route('roles.destroy',$viewer->role),['version'=>1])->assertSessionHasErrors('role');
    }
    public function test_role_crud_permission_matrix_applies_on_next_request(): void
    {
        $this->signIn();
        $this->post(route('roles.store'),['name'=>'Dispatch','permissions'=>['rates.create']])->assertSessionHasNoErrors();
        $role = Role::where('name','Dispatch')->firstOrFail();
        $this->assertContains('rates.view',$role->permissions);
        $user = User::create($this->payload(['role_id'=>$role->id]));
        $this->get(route('roles.edit',$role))->assertOk()->assertSee('Save role');
        $this->put(route('roles.update',$role),['name'=>'Dispatch team','permissions'=>['rates.view','transporters.view'],'version'=>1])->assertSessionHasNoErrors();
        $this->assertSame(1,$user->fresh()->session_version);
        $this->assertTrue($user->fresh('role')->allows('transporters.view'));
        $this->assertFalse($user->fresh('role')->allows('rates.create'));
        $this->put(route('roles.update',$role),['name'=>'Dispatch team','permissions'=>['not.real'],'version'=>2])->assertSessionHasErrors('permissions.0');
        $this->delete(route('users.destroy',$user),['version'=>2])->assertSessionHasNoErrors();
        $this->delete(route('roles.destroy',$role),['version'=>2])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('roles',['id'=>$role->id]);
    }
    public function test_role_permissions_include_required_actions_and_user_preview(): void
    {
        $admin=$this->signIn();
        $this->post(route('roles.store'),['name'=>'CRM coordinator','permissions'=>['leads.email','leads.automations','users.create','products.edit','rates.export','transporters.delete']])->assertSessionHasNoErrors();
        $role=Role::where('name','CRM coordinator')->firstOrFail();
        foreach(['leads.view','leads.activity','leads.broadcast','users.view','users.assign_roles','products.view','rates.view','transporters.view'] as $required)$this->assertContains($required,$role->permissions);
        $user=User::create($this->payload(['email'=>'coordinator@example.test','role_id'=>$role->id]));
        $this->get(route('access.index'))->assertOk()->assertSee('View effective access')->assertSee('CRM coordinator');
        $this->actingAs($user)->withSession(['last_activity_at' => now()->timestamp, 'login_expires_at' => now('Asia/Kolkata')->startOfDay()->addDay()->timestamp, 'session_version' => 1]);
        $this->get(route('leads.index'))->assertOk();
        $this->get(route('rates.index'))->assertOk();
        $this->get(route('transporters.index'))->assertOk();
        $this->post(route('roles.store'),['name'=>'No access'])->assertForbidden();
    }
    public function test_updated_role_grants_and_revokes_access_without_a_new_login(): void
    {
        $admin=$this->signIn();
        $role=Role::create(['name'=>'Operations test','permissions'=>['rates.view']]);
        $user=User::create($this->payload(['email'=>'operations@example.test','role_id'=>$role->id]));
        $this->signIn($user);
        $this->get(route('rates.index'))->assertOk();
        $this->get(route('transporters.index'))->assertForbidden();

        $this->signIn($admin);
        $this->put(route('roles.update',$role),['name'=>$role->name,'permissions'=>['transporters.view'],'version'=>$role->version])->assertSessionHasNoErrors();
        $this->assertSame(1,$user->fresh()->session_version);

        $this->actingAs($user)->withSession(['last_activity_at'=>now()->timestamp,'login_expires_at'=>now('Asia/Kolkata')->startOfDay()->addDay()->timestamp,'session_version'=>1]);
        $this->get(route('transporters.index'))->assertOk();
        $this->get(route('rates.index'))->assertForbidden();
    }
    public function test_changing_a_users_role_updates_access_without_signing_them_out(): void
    {
        $admin = $this->signIn();
        $ratesRole = Role::create(['name' => 'Rates only', 'permissions' => ['rates.view']]);
        $transportRole = Role::create(['name' => 'Transport only', 'permissions' => ['transporters.view']]);
        $user = User::create($this->payload(['email' => 'switch@example.test', 'role_id' => $ratesRole->id]));

        $this->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'active' => 1,
            'role_id' => $transportRole->id, 'version' => $user->version,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $user->fresh()->session_version);

        $this->actingAs($user)->withSession(['last_activity_at' => now()->timestamp, 'login_expires_at' => now('Asia/Kolkata')->startOfDay()->addDay()->timestamp, 'session_version' => 1]);
        $this->get(route('transporters.index'))->assertOk();
        $this->get(route('rates.index'))->assertForbidden();
    }
    public function test_delegated_manager_cannot_escalate_privileges(): void
    {
        $role = Role::create(['name'=>'Limited administrator','permissions'=>['users.view','users.create','users.edit','users.assign_roles','roles.view','roles.create','roles.edit']]);
        $user = User::create($this->payload(['role_id'=>$role->id])); $this->signIn($user);
        $this->post(route('users.store'),$this->payload(['email'=>'other@example.test','role_id'=>Role::where('is_admin',true)->firstOrFail()->id]))->assertForbidden();
        $this->post(route('roles.store'),['name'=>'Escalation','permissions'=>['rates.delete']])->assertForbidden();
        $this->assertDatabaseMissing('roles',['name'=>'Escalation']);
    }
}
