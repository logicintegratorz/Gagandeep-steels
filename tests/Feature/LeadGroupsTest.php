<?php
namespace Tests\Feature;

use App\Models\{Lead,Role,User};
use Tests\TestCase;

final class LeadGroupsTest extends TestCase
{
    public function test_user_can_create_list_add_lead_and_see_its_tab():void
    {
        $admin=$this->signIn();
        $lead=Lead::create(['name'=>'Steel buyer','owner_id'=>$admin->id,'stage'=>'New']);
        $this->post(route('lead-groups.store'),['name'=>'Priority buyers','lead_id'=>$lead->id])->assertRedirect();
        $this->get(route('lead-groups.index'))->assertOk()->assertSee('Priority buyers')->assertSee('Steel buyer')
            ->assertSee('lead-group-page-tabs')->assertDontSee('Lead connections');
        $this->get(route('leads.show',$lead))->assertOk()->assertSee('Priority buyers')->assertSee($admin->name)->assertDontSee('Added by '.$admin->name);
        $group=\App\Models\LeadGroup::firstOrFail();
        $this->post(route('lead-groups.members.add-selected'),['group_id'=>$group->id,'lead_id'=>$lead->id])->assertRedirect();
        $this->assertSame(1,$group->leads()->count());
        $this->delete(route('lead-groups.members.remove',[$group,$lead]))->assertRedirect();
        $this->assertSame(0,$group->leads()->count());
    }

    public function test_lists_and_members_are_private_to_their_creator():void
    {
        $admin=$this->signIn();
        $lead=Lead::create(['name'=>'Admin lead','owner_id'=>$admin->id,'stage'=>'New']);
        $this->post(route('lead-groups.store'),['name'=>'Private list','lead_id'=>$lead->id])->assertRedirect();
        $group=\App\Models\LeadGroup::firstOrFail();
        $role=Role::create(['name'=>'Lead viewer','permissions'=>['leads.view']]);
        $user=User::create(['name'=>'Other user','email'=>'other-list@example.test','password'=>'Testing1234','active'=>true,'role_id'=>$role->id]);
        $this->signIn($user);
        $this->get(route('lead-groups.index'))->assertOk()->assertDontSee('Private list');
        $this->post(route('lead-groups.members.add-selected'),['group_id'=>$group->id,'lead_id'=>$lead->id])->assertNotFound();
    }
}
