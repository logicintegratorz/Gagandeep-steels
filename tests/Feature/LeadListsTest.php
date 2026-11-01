<?php
namespace Tests\Feature;

use App\Models\{Lead,LeadList,LeadListAutomation,LeadListDelivery,LeadSetting,Role,User};
use App\Support\LeadListAutomations;
use App\Support\LeadListQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class LeadListsTest extends TestCase {
 public function test_lists_membership_selected_whatsapp_links_without_api(){
  $admin=$this->signIn();
  $first=Lead::create(['name'=>'First buyer','owner_id'=>$admin->id,'whatsapp'=>'+919876543210']);
  $second=Lead::create(['name'=>'Second buyer','owner_id'=>$admin->id,'whatsapp'=>'+919876543211']);
  $third=Lead::create(['name'=>'Third buyer','owner_id'=>$admin->id,'whatsapp'=>'+919876543212']);
  $this->post(route('leads.lists.store'),['name'=>'Daily prices','description'=>'Today steel prices are ready.'])->assertRedirect();
  $list=LeadList::firstOrFail();
  $this->post(route('leads.lists.store'),['name'=>'VIP buyers','description'=>'Welcome VIP buyer','lead_id'=>$first->id])->assertSessionHasNoErrors();
  $this->assertTrue(LeadList::where('name','VIP buyers')->firstOrFail()->members()->whereKey($first->id)->exists());
  $this->get(route('leads.lists'))->assertOk()->assertSee('List Manager')->assertSee('data-open-list-dialog',false)->assertSee('<dialog id="create-list"',false)->assertSee('Daily prices')->assertSee('Settings for Daily prices');
  $this->get(route('leads.lists',['lead_id'=>$first->id]))->assertOk()->assertSee('data-open-on-load',false)->assertSee('First buyer will be added automatically.');
  $this->post(route('leads.lists.members.add',$list),['lead_ids'=>[$first->id,$second->id,$third->id]])->assertSessionHasNoErrors();
  $this->get(route('leads.lists.show',$list))->assertOk()->assertSee('First buyer')->assertSee('Second buyer')->assertSee('aria-current="page"',false);
  $this->get(route('leads.show',$first))->assertOk()->assertSee('Daily prices')->assertSee('Choose a list')->assertSee('+ New list')->assertSee('Opportunity');
  $key=(string)\Illuminate\Support\Str::uuid();
  $link='https://wa.me/919876543210?text=Today%20steel%20prices%20are%20ready.';
  $this->post(route('leads.lists.send',$list),['channel'=>'WhatsApp','lead_ids'=>[$first->id],'request_key'=>$key])->assertOk()->assertSee($link);
  $this->post(route('leads.lists.send-all',$list),['channel'=>'WhatsApp','request_key'=>(string)\Illuminate\Support\Str::uuid()])->assertOk()->assertSee('3 selected contacts')->assertSee('Open next chat')->assertSee($link)->assertSee('https://wa.me/919876543211?text=Today%20steel%20prices%20are%20ready.')->assertSee('https://wa.me/919876543212?text=Today%20steel%20prices%20are%20ready.');
  $this->assertSame(0,LeadListDelivery::count());
  $this->post(route('leads.lists.automations.store',$list),['channel'=>'WhatsApp','frequency'=>'Once','send_time'=>'09:00'])->assertSessionHasErrors('channel');
  $this->assertSame(0,LeadListAutomation::count());
  $this->get(route('leads.lists.whatsapp',$list))->assertOk()->assertSee($link);
  $this->delete(route('leads.lists.members.remove',[$list,$first]))->assertSessionHasNoErrors();
  $this->assertSame(2,$list->members()->count());
 }
 public function test_whatsapp_schedule_sends_approved_template_automatically_and_can_be_paused(){
  $admin=$this->signIn();
  $lead=Lead::create(['name'=>'Buyer','owner_id'=>$admin->id,'phone'=>'9876543210']);
  $list=LeadList::create(['name'=>'Daily buyers','description'=>'Hello buyer','created_by'=>$admin->id]);$list->members()->attach($lead);
  $settings=LeadSetting::findOrFail(1);$settings->whatsapp_config=['phone_number_id'=>'12345678901','api_version'=>'v24.0','template_name'=>'list_message','language_code'=>'en_US'];$settings->whatsapp_token='test-token';$settings->save();
  $this->post(route('leads.lists.automations.store',$list),['channel'=>'WhatsApp','frequency'=>'Daily','send_time'=>'09:00'])->assertSessionHasNoErrors();
  $schedule=LeadListAutomation::firstOrFail();
  $this->post(route('leads.lists.automations.toggle',[$list,$schedule]))->assertSessionHasNoErrors();
  $this->assertFalse($schedule->fresh()->active);
  $this->post(route('leads.lists.automations.toggle',[$list,$schedule]))->assertSessionHasNoErrors();
  $schedule->update(['next_run_at'=>now('Asia/Kolkata')->subMinute()]);
  Http::fake(['graph.facebook.com/*'=>Http::response(['messages'=>[['id'=>'wamid.test123']]],200)]);
  $this->assertSame(1,LeadListAutomations::run());
  $delivery=LeadListDelivery::firstOrFail();
  $this->assertSame('Accepted',$delivery->status);
  $this->assertSame('wamid.test123',$delivery->provider_id);
  $this->assertSame('919876543210',$delivery->recipient);
  Http::assertSent(fn($request)=>$request->url()==='https://graph.facebook.com/v24.0/12345678901/messages'&&$request['template']['components'][0]['parameters'][0]['text']==='Hello buyer'&&$request['to']==='919876543210');
  $this->assertTrue($schedule->fresh()->next_run_at->isFuture());
 }
 public function test_whatsapp_api_rejection_is_visible_as_failed_send(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Buyer','owner_id'=>$admin->id,'phone'=>'9876543210']);
  $list=LeadList::create(['name'=>'Buyers','description'=>'Offer','created_by'=>$admin->id]);$list->members()->attach($lead);
  $settings=LeadSetting::findOrFail(1);$settings->whatsapp_config=['phone_number_id'=>'12345678901','api_version'=>'v24.0','template_name'=>'list_message','language_code'=>'en_US'];$settings->whatsapp_token='test-token';$settings->save();
  $schedule=$list->automations()->create(['channel'=>'WhatsApp','frequency'=>'Once','send_time'=>'09:00','active'=>true,'next_run_at'=>now('Asia/Kolkata')->subMinute()]);
  Http::fake(['graph.facebook.com/*'=>Http::response(['error'=>['message'=>'Template is not approved']],400)]);
  $this->assertSame(1,LeadListAutomations::run());
  $this->assertSame('Failed',LeadListDelivery::firstOrFail()->status);
  $this->assertFalse($schedule->fresh()->active);
 }
 public function test_email_schedule_sends_list_message_at_due_time(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Email buyer','owner_id'=>$admin->id,'email'=>'buyer@example.test']);
  $list=LeadList::create(['name'=>'Price update','description'=>'Today’s price is ready','created_by'=>$admin->id]);$list->members()->attach($lead);
  $settings=LeadSetting::findOrFail(1);$settings->email_config=['host'=>'smtp.example.test','port'=>587,'username'=>'sender@example.test','from_address'=>'sender@example.test','from_name'=>'Sales'];$settings->email_password='test-password';$settings->save();
  $this->post(route('leads.lists.automations.store',$list),['channel'=>'Email','frequency'=>'Once','send_time'=>'09:00'])->assertSessionHasNoErrors();
  $schedule=LeadListAutomation::firstOrFail();$schedule->update(['next_run_at'=>now('Asia/Kolkata')->subMinute()]);
  Mail::fake();
  $this->assertSame(1,LeadListAutomations::run());
  Mail::assertSent(\App\Mail\LeadEmail::class,1);
  $this->assertSame('Accepted',LeadListDelivery::firstOrFail()->status);
  $this->assertFalse($schedule->fresh()->active);
 }
 public function test_whatsapp_link_uses_registered_indian_mobile_and_encoded_message(){
  $lead=new Lead(['name'=>'Buyer','phone'=>'09876543210']);
  $this->assertSame('https://wa.me/919876543210?text=Hello%20%26%20welcome',\App\Support\LeadWhatsAppLink::for($lead,'Hello & welcome'));
  $lead->whatsapp='+91 98765 43211';
  $this->assertSame('https://wa.me/919876543211?text=Hi',\App\Support\LeadWhatsAppLink::for($lead,'Hi'));
  $lead->whatsapp='123';
  $this->assertNull(\App\Support\LeadWhatsAppLink::for($lead,'Hi'));
 }
 public function test_list_settings_menu_and_deactivation_pause_sending(){
  $admin=$this->signIn();
  $lead=Lead::create(['name'=>'Menu buyer','owner_id'=>$admin->id,'phone'=>'9876543210']);
  $list=LeadList::create(['name'=>'Menu list','description'=>'Hello buyer','created_by'=>$admin->id]);$list->members()->attach($lead);
  $this->get(route('leads.lists'))->assertOk()->assertSee('Settings for Menu list')->assertSee('Deactivate list')->assertSee('Rename list')->assertSee('Schedule messages');
  $this->get(route('leads.lists.show',$list))->assertOk()->assertSee('Settings for Menu buyer')->assertSee('Open WhatsApp chat')->assertSee('Remove from list');
  $this->post(route('leads.lists.active',$list))->assertSessionHasNoErrors();
  $this->assertFalse($list->fresh()->active);
  $this->post(route('leads.lists.send-all',$list),['channel'=>'WhatsApp','request_key'=>(string)\Illuminate\Support\Str::uuid()])->assertStatus(409);
  $this->get(route('leads.lists.show',$list))->assertOk()->assertSee('Activate list')->assertDontSee('form="whatsapp-'.$lead->id.'"',false);
  $this->post(route('leads.lists.active',$list))->assertSessionHasNoErrors();
  $this->assertTrue($list->fresh()->active);
 }
 public function test_staff_cannot_manage_another_users_list_or_send_without_permission(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Buyer','owner_id'=>$admin->id]);$list=LeadList::create(['name'=>'Private list','description'=>'Message','created_by'=>$admin->id]);$list->members()->attach($lead);
  $role=Role::create(['name'=>'Lead list editor','permissions'=>['leads.view','leads.lists']]);
  $reader=User::create(['name'=>'Reader','email'=>'list-reader@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $this->signIn($reader);
  $this->get(route('leads.lists'))->assertOk()->assertDontSee('Private list');
  $this->get(route('leads.lists.show',$list))->assertNotFound();
  $this->post(route('leads.lists.store'),['name'=>'Own list','description'=>'Own'])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('lead_lists',['name'=>'Own list','created_by'=>$reader->id]);
  $this->post(route('leads.lists.send',$list),['channel'=>'Email','lead_ids'=>[$lead->id]])->assertNotFound();
  $viewOnly=Role::create(['name'=>'Lead viewer','permissions'=>['leads.view']]);
  $viewer=User::create(['name'=>'Viewer','email'=>'list-viewer@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$viewOnly->id]);
  $this->signIn($viewer);
  $this->get(route('leads.lists'))->assertForbidden();
  $this->post(route('leads.lists.store'),['name'=>'No','description'=>'No'])->assertForbidden();
 }
}
