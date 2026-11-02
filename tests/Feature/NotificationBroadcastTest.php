<?php
namespace Tests\Feature;

use App\Models\{CrmNotification, NotificationBroadcast, Role, User};
use Illuminate\Support\Str;
use Tests\TestCase;

final class NotificationBroadcastTest extends TestCase
{
    private function user(string $email, string $role='Viewer', bool $active=true): User
    {
        return User::create(['name'=>$email,'email'=>$email,'password'=>'Pass1234','role_id'=>Role::where('name',$role)->firstOrFail()->id,'active'=>$active]);
    }
    private function message(array $overrides=[]): array
    {
        return array_merge(['submission_key'=>(string)Str::uuid(),'title'=>'Dispatch update','message'=>'Please check the new dispatch schedule.','audience'=>'all'],$overrides);
    }
    public function test_default_non_admin_roles_cannot_send_or_manage_notifications(): void
    {
        foreach(['Manager','Operator','Viewer'] as $name) {
            $user=$this->user(strtolower($name).'@example.test',$name); $this->signIn($user);
            $this->get(route('notifications.index'))->assertOk()->assertDontSee('Send notification')->assertDontSee('Sent notifications');
            $this->get(route('broadcasts.compose'))->assertForbidden();
            $this->get(route('broadcasts.index'))->assertForbidden();
            $this->post(route('broadcasts.store'),$this->message())->assertForbidden();
        }
        $this->assertDatabaseCount('notification_broadcasts',0);
    }
    public function test_admin_broadcasts_to_active_accounts_only_and_retries_are_idempotent(): void
    {
        $admin=$this->signIn(); $viewer=$this->user('viewer@example.test'); $inactive=$this->user('inactive@example.test','Viewer',false);
        $this->get(route('broadcasts.compose'))->assertOk()->assertSee('Compose message');
        $data=$this->message();
        $this->post(route('broadcasts.store'),$data)->assertRedirect(route('broadcasts.compose'))->assertSessionHasNoErrors();
        $broadcast=NotificationBroadcast::firstOrFail();
        $this->assertSame(2,$broadcast->recipient_count);
        foreach([$admin,$viewer] as $recipient) $this->assertDatabaseHas('crm_notifications',['user_id'=>$recipient->id,'broadcast_id'=>$broadcast->id]);
        $this->assertDatabaseMissing('crm_notifications',['user_id'=>$inactive->id]);
        $this->post(route('broadcasts.store'),$data)->assertSessionHas('status');
        $this->assertDatabaseCount('notification_broadcasts',1);$this->assertDatabaseCount('crm_notifications',2);
    }
    public function test_selected_users_and_roles_limit_recipients(): void
    {
        $admin=$this->signIn();$viewer=$this->user('viewer@example.test');$operator=$this->user('operator@example.test','Operator');
        $this->post(route('broadcasts.store'),$this->message(['audience'=>'users','user_ids'=>[$viewer->id]]))->assertSessionHasNoErrors();
        $first=NotificationBroadcast::firstOrFail();
        $this->assertEquals([$viewer->id],$first->deliveries()->pluck('user_id')->all());
        $this->post(route('broadcasts.store'),$this->message(['audience'=>'roles','role_ids'=>[$operator->role_id]]))->assertSessionHasNoErrors();
        $second=NotificationBroadcast::orderByDesc('id')->firstOrFail();
        $this->assertEquals([$operator->id],$second->deliveries()->pluck('user_id')->all());
        $this->assertDatabaseMissing('crm_notifications',['broadcast_id'=>$second->id,'user_id'=>$admin->id]);
    }
    public function test_notification_title_is_optional_and_stays_empty(): void
    {
        $this->signIn();
        $this->post(route('broadcasts.store'),$this->message(['title'=>'   ']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notification_broadcasts',['title'=>'']);
        $this->assertDatabaseHas('crm_notifications',['title'=>'']);
    }
    public function test_invalid_or_empty_audience_does_not_send(): void
    {
        $this->signIn();$inactive=$this->user('inactive@example.test','Viewer',false);
        $this->post(route('broadcasts.store'),$this->message(['audience'=>'users','user_ids'=>[]]))->assertSessionHasErrors('user_ids');
        $this->post(route('broadcasts.store'),$this->message(['audience'=>'users','user_ids'=>[$inactive->id]]))->assertSessionHasErrors('audience');
        $this->post(route('broadcasts.store'),$this->message(['title'=>str_repeat('a',121)]))->assertSessionHasErrors('title');
        $this->post(route('broadcasts.store'),$this->message(['message'=>str_repeat('a',2001)]))->assertSessionHasErrors('message');
        $this->assertDatabaseCount('notification_broadcasts',0);
    }
    public function test_only_administrator_can_send_or_manage_messages(): void
    {
        $admin=$this->signIn();
        $this->post(route('roles.store'),['name'=>'Communication team','permissions'=>['notifications.send','notifications.recall','roles.create','roles.edit','users.create','users.assign_roles']])->assertSessionHasNoErrors();
        $role=Role::where('name','Communication team')->firstOrFail();
        $this->assertContains('notifications.view',$role->permissions);
        $delegate=User::create(['name'=>'Communicator','email'=>'communicator@example.test','password'=>'Pass1234','active'=>true,'role_id'=>$role->id]);
        $this->signIn($delegate);
        $this->get(route('broadcasts.compose'))->assertForbidden();
        $this->post(route('broadcasts.store'),$this->message())->assertForbidden();
        $this->get(route('broadcasts.index'))->assertForbidden();
        $this->post(route('roles.store'),['name'=>'Unauthorized grant','permissions'=>['notifications.send']])->assertForbidden();
        $this->assertDatabaseMissing('roles',['name'=>'Unauthorized grant']);
        $this->post(route('users.store'),['name'=>'Unauthorized recipient','email'=>'no-grant@example.test','password'=>'Pass1234','password_confirmation'=>'Pass1234','role_id'=>$role->id,'active'=>1])->assertForbidden();
        $this->assertDatabaseMissing('users',['email'=>'no-grant@example.test']);
        $this->get(route('roles.edit',$role))->assertOk()->assertSee('Administrator managed');
    }
    public function test_notification_only_role_can_open_dashboard_and_its_inbox(): void
    {
        $role=Role::create(['name'=>'Announcements','permissions'=>['notifications.view','notifications.send']]);
        $user=User::create(['name'=>'Announcements user','email'=>'announcements@example.test','password'=>'Pass1234','active'=>true,'role_id'=>$role->id]);
        $this->signIn($user);
        $this->get(route('home'))->assertOk()->assertViewIs('dashboard');
        $this->get(route('notifications.index'))->assertOk()->assertSee('Current Notification')->assertDontSee('Send notification');
        $this->get(route('rates.index'))->assertForbidden();
    }
    public function test_delegates_cannot_view_or_recall_others_broadcasts_and_admin_can_recall(): void
    {
        $admin=$this->signIn();$this->post(route('broadcasts.store'),$this->message(['title'=>'Administrator message']))->assertSessionHasNoErrors();
        $broadcast=NotificationBroadcast::firstOrFail();
        $role=Role::create(['name'=>'Communication','permissions'=>['notifications.view','notifications.send','notifications.recall']]);
        $delegate=User::create(['name'=>'Delegate','email'=>'delegate@example.test','password'=>'Pass1234','active'=>true,'role_id'=>$role->id]);
        $this->signIn($delegate);
        $this->get(route('broadcasts.index'))->assertForbidden();
        $this->post(route('broadcasts.recall',$broadcast))->assertForbidden();
        $this->signIn($admin);
        $this->post(route('broadcasts.recall',$broadcast))->assertSessionHas('success');
        $this->assertNotNull($broadcast->fresh()->recalled_at);
        $this->get(route('notifications.index'))->assertDontSee('Administrator message');
        $this->get(route('broadcasts.index'))->assertSee('Administrator message')->assertSee('Recalled');
        $this->getJson(route('notifications.count'))->assertJsonPath('unread',0);
    }
    public function test_sent_read_counts_survive_dismissal_and_message_html_is_escaped(): void
    {
        $admin=$this->signIn();$viewer=$this->user('viewer@example.test');
        $data=$this->message(['audience'=>'users','user_ids'=>[$viewer->id],'message'=>'<script>alert(1)</script>']);
        $this->post(route('broadcasts.store'),$data)->assertSessionHasNoErrors();
        $entry=CrmNotification::where('user_id',$viewer->id)->firstOrFail();
        $this->signIn($viewer);
        $this->get(route('notifications.index'))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
        $this->delete(route('notifications.destroy',$entry->id))->assertSessionHas('success');
        $this->signIn($admin);
        $response=$this->get(route('broadcasts.index'))->assertOk();
        $this->assertSame(1,$response->viewData('broadcasts')->first()->read_count);
    }
    public function test_admin_can_edit_and_delete_sent_message_records(): void
    {
        $admin = $this->signIn();
        $viewer = $this->user('viewer@example.test');
        $this->post(route('broadcasts.store'), $this->message(['audience'=>'users', 'user_ids'=>[$viewer->id]]))->assertSessionHasNoErrors();
        $broadcast = NotificationBroadcast::firstOrFail();
        $this->put(route('broadcasts.update', $broadcast), ['title'=>'Updated dispatch', 'message'=>'Updated delivery details.'])->assertSessionHas('success');
        $this->assertDatabaseHas('notification_broadcasts', ['id'=>$broadcast->id, 'title'=>'Updated dispatch', 'message'=>'Updated delivery details.']);
        $this->assertDatabaseHas('crm_notifications', ['broadcast_id'=>$broadcast->id, 'title'=>'Updated dispatch']);
        $this->delete(route('broadcasts.destroy', $broadcast))->assertSessionHas('success');
        $this->assertDatabaseMissing('notification_broadcasts', ['id'=>$broadcast->id]);
        $this->assertDatabaseMissing('crm_notifications', ['broadcast_id'=>$broadcast->id]);
    }
}
