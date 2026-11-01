<?php
namespace Tests\Feature;
use App\Models\{Role,User};
use Tests\TestCase;
final class NotificationSoundTest extends TestCase {
 public function test_admin_controls_global_sound(): void {
  $this->signIn();
  $this->get(route('notifications.index'))->assertOk()->assertSee('Notification sound:');
  $this->get(route('notifications.count'))->assertJson(['sound_enabled'=>true,'latest_message'=>0]);
  $this->post(route('notifications.sound'),['enabled'=>0])->assertRedirect();
  $this->get(route('notifications.count'))->assertJson(['sound_enabled'=>false]);
  $role=Role::create(['name'=>'Sound reader','permissions'=>[]]);
  $user=User::create(['name'=>'Reader','email'=>'sound@example.test','password'=>'Testing1234','active'=>true,'role_id'=>$role->id]);
  $this->signIn($user);
  $this->post(route('notifications.sound'),['enabled'=>1])->assertForbidden();
  $this->get(route('notifications.count'))->assertJson(['sound_enabled'=>false]);
 }
}
