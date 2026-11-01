<?php
namespace Tests\Feature;

use App\Models\{CrmNotification, Role, User};
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class NotificationsAndValidationTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge(['name'=>'Team member','email'=>'member@example.test','password'=>'Pass1234','password_confirmation'=>'Pass1234','role_id'=>Role::where('name','Viewer')->firstOrFail()->id,'active'=>1], $overrides);
    }
    private function notice(User $user, array $overrides = []): CrmNotification
    {
        return CrmNotification::create(array_merge(['user_id'=>$user->id,'title'=>'Account update','message'=>'Your account changed.','permission'=>null], $overrides));
    }
    public function test_eight_character_password_accepted_and_short_password_rejected(): void
    {
        $admin = $this->signIn();
        $this->post(route('users.store'),$this->payload(['password'=>'Pass123','password_confirmation'=>'Pass123']))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('crm_notifications',0);
        $this->post(route('users.store'),$this->payload())->assertSessionHasNoErrors();
        $user=User::where('email','member@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Pass1234',$user->password));
        $this->assertDatabaseHas('crm_notifications',['user_id'=>$admin->id,'title'=>'User created']);
        $this->assertDatabaseHas('crm_notifications',['user_id'=>$user->id,'title'=>'Welcome to Gagandeep Steels']);
        $this->assertStringNotContainsString('Pass1234',CrmNotification::all()->toJson());
    }
    public function test_email_format_normalization_and_duplicates(): void
    {
        $this->signIn();
        foreach(['not-email','name@localhost','name@@example.com','name@-bad.com','name@example..com'] as $email) {
            $this->post(route('users.store'),$this->payload(['email'=>$email]))->assertSessionHasErrors('email');
        }
        $this->post(route('users.store'),$this->payload(['email'=>'  MEMBER@EXAMPLE.TEST  ']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users',['email'=>'member@example.test']);
        $this->post(route('users.store'),$this->payload())->assertSessionHasErrors('email');
        $this->assertSame(1,User::where('email','member@example.test')->count());
    }
    public function test_notifications_are_private_and_can_be_read_filtered_and_dismissed(): void
    {
        $admin=$this->signIn(); $other=User::create($this->payload());
        $own=$this->notice($admin); $private=$this->notice($other,['message'=>'Private to other user']);
        $this->get(route('notifications.index'))->assertOk()->assertSee('Your account changed.')->assertDontSee('Private to other user');
        $this->getJson(route('notifications.count'))->assertJsonPath('unread',1);
        $this->patch(route('notifications.read',$private->id))->assertNotFound();
        $this->delete(route('notifications.destroy',$private->id))->assertNotFound();
        $this->patch(route('notifications.read',$own->id))->assertSessionHas('success');
        $this->assertNotNull($own->fresh()->read_at);
        $this->get(route('notifications.index',['status'=>'unread']))->assertDontSee('Your account changed.');
        $this->get(route('notifications.index',['status'=>'read']))->assertSee('Your account changed.');
        $this->delete(route('notifications.destroy',$own->id))->assertSessionHas('success');
        $this->assertNotNull($own->fresh()->dismissed_at);
        $this->get(route('notifications.index'))->assertDontSee('Your account changed.');
        $this->assertDatabaseHas('crm_notifications',['id'=>$private->id,'read_at'=>null]);
    }
    public function test_current_permissions_filter_notifications_and_bulk_reads(): void
    {
        $viewer=User::create($this->payload());$this->signIn($viewer);
        $allowed=$this->notice($viewer,['permission'=>'rates.view','title'=>'Freight update']);
        $hidden=$this->notice($viewer,['permission'=>'users.view','title'=>'Restricted administration']);
        $this->get(route('notifications.index'))->assertSee('Freight update')->assertDontSee('Restricted administration');
        $this->getJson(route('notifications.count'))->assertJsonPath('unread',1);
        $this->patch(route('notifications.read',$hidden->id))->assertNotFound();
        $this->post(route('notifications.read-all'))->assertSessionHas('success');
        $this->assertNotNull($allowed->fresh()->read_at);$this->assertNull($hidden->fresh()->read_at);
    }
    public function test_notification_polling_does_not_extend_idle_session(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-01 10:00:00','Asia/Kolkata'));
        $this->signIn(); $initial=now()->timestamp;
        $this->travel(29)->minutes();
        $this->getJson(route('notifications.count'))->assertOk()->assertSessionHas('last_activity_at',$initial);
        $this->travel(1)->minutes();
        $this->getJson(route('notifications.count'))->assertUnauthorized();
        $this->travelBack();
    }
    public function test_business_activity_notifies_actor_and_administrators_without_password_data(): void
    {
        $admin=User::create($this->payload(['email'=>'admin@example.test','role_id'=>Role::where('is_admin',true)->firstOrFail()->id]));
        $operator=User::create($this->payload(['email'=>'operator@example.test','role_id'=>Role::where('name','Operator')->firstOrFail()->id]));
        $this->signIn($operator);
        $this->post(route('transporters.store'),['name'=>'Test transport','contact_person'=>'Dispatch','phone'=>'9876543210','address'=>'Test address'])->assertSessionHasNoErrors();
        foreach([$admin,$operator] as $recipient) $this->assertDatabaseHas('crm_notifications',['user_id'=>$recipient->id,'title'=>'Transporter added','permission'=>'transporters.view']);
        $this->assertDatabaseCount('crm_notifications',2);
    }
    public function test_account_reset_notifies_target_and_does_not_expose_password(): void
    {
        $this->signIn(); $user=User::create($this->payload());
        $this->put(route('users.update',$user),['name'=>$user->name,'email'=>$user->email,'active'=>1,'version'=>1,'password'=>'Reset123','password_confirmation'=>'Reset123'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('crm_notifications',['user_id'=>$user->id,'title'=>'Your account was updated','permission'=>null]);
        $this->assertStringNotContainsString('Reset123',CrmNotification::all()->toJson());
    }
}
