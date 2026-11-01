<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Lead,LeadSetting,LeadActivity,LeadConnection,User,Role};
use App\Support\{LeadSync,LeadImport};
use Illuminate\Support\Facades\{Mail,Http,DB};
use Illuminate\Support\Str;
class LeadDetailsTest extends TestCase {
 public function test_call_logs_require_and_display_the_selected_direction(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Buyer','owner_id'=>$admin->id]);
  $this->post(route('leads.activities.store',$lead),['kind'=>'Call log','body'=>'Discussed a quote','request_key'=>(string)Str::uuid()])->assertSessionHasErrors('call_direction');
  foreach(['Incoming','Outgoing'] as $direction){
   $this->post(route('leads.activities.store',$lead),['kind'=>'Call log','call_direction'=>$direction,'body'=>$direction.' discussion','request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
   $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'kind'=>'Call log','action'=>$direction.' call','details'=>$direction.' discussion']);
  }
  $this->post(route('leads.activities.store',$lead),['kind'=>'Call log','call_direction'=>'Missed call','subject'=>'Customer unavailable','body'=>'Retry tomorrow morning','request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'kind'=>'Call log','action'=>'Missed call: Customer unavailable','details'=>'Retry tomorrow morning']);
  $this->get(route('leads.show',[$lead,'activity'=>'Call log','call_filter'=>'missed']))->assertOk()->assertSee('Retry tomorrow morning')->assertDontSee('Incoming discussion');
  $this->get(route('home'))->assertOk()->assertSee('Missed Calls')->assertSee('1');
  $this->get(route('dashboard.metric','missed-calls'))->assertOk()->assertSee('Missed call')->assertSee('Retry tomorrow morning');
  $legacy=$lead->activities()->create(['actor'=>'Admin','kind'=>'Call log','action'=>'Call log','details'=>'Older call']);
  $this->put(route('leads.activities.update',[$lead,$legacy]),['version'=>$legacy->fresh()->version,'subject'=>'Call log','body'=>'Older call','call_direction'=>'Incoming'])->assertSessionHasNoErrors();
  $this->assertSame('Incoming',$legacy->fresh()->call_direction);
  $this->get(route('leads.show',[$lead,'activity'=>'Call log']))->assertOk()->assertSee('Incoming call')->assertSee('Outgoing call');
 }
 public function test_phone_qr_call_outcomes_and_message_logs_are_recorded(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Call Buyer','owner_id'=>$admin->id,'phone'=>'+91 98765-43210']);
  $page=$this->get(route('leads.show',$lead))->assertOk()->assertSee('data-lead-call-open',false)->assertSee('Call Answered')->assertSee('Call Unanswered')->assertDontSee('data-lead-call-close',false);
  $this->assertStringContainsString('data-lead-call-dialog',$page->getContent());
  $page->assertSee('href="tel:+919876543210"',false)->assertSee('Need help calling / connect mobile')->assertSee('https://phonelink.microsoft.com/',false);
  $this->get(route('leads.call.qr',$lead))->assertOk()->assertHeader('content-type','image/svg+xml')->assertSee('<svg',false);
  $this->postJson(route('leads.call.start',$lead))->assertOk()->assertJson(['locked'=>true]);
  $this->assertSame($lead->id,session('active_lead_call_id'));
  $this->getJson(route('session.status'))->assertOk()->assertJsonStructure(['idleExpiresAt','midnightExpiresAt','serverNow']);
  $this->getJson(route('notifications.count'))->assertOk();
  $this->get(route('home'))->assertRedirect(route('leads.show',$lead));
  $this->post(route('leads.call.outcome',$lead),['outcome'=>'Connected','details'=>'','request_key'=>(string)Str::uuid()])->assertSessionHasErrors('details');
  $this->assertSame($lead->id,session('active_lead_call_id'));
  $this->post(route('leads.call.outcome',$lead),['outcome'=>'Connected','details'=>'Buyer requested the latest rates.','request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
  $this->assertNull(session('active_lead_call_id'));
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'kind'=>'Call log','action'=>'Outgoing call: Connected','details'=>'Buyer requested the latest rates.']);
  $this->post(route('leads.call.outcome',$lead),['outcome'=>'Connected','details'=>'Return to the selected dashboard contact.','return_to_dashboard'=>1,'request_key'=>(string)Str::uuid()])
   ->assertRedirect(route('home',['contacts'=>$lead->id]).'#contacts-table');
  $this->postJson(route('leads.call.start',$lead))->assertOk();
  $unanswered=['outcome'=>'Unanswered','details'=>'','request_key'=>(string)Str::uuid()];
  $this->post(route('leads.call.outcome',$lead),$unanswered)->assertSessionHasNoErrors();
  $this->assertNull(session('active_lead_call_id'));
  $this->post(route('leads.call.outcome',$lead),$unanswered)->assertSessionHasNoErrors();
  $this->assertSame(1,$lead->activities()->where('request_key',$unanswered['request_key'])->count());
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'action'=>'Outgoing call: Unanswered','details'=>'']);
  $this->post(route('leads.call.outcome',$lead),['outcome'=>'Unanswered','request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
  $this->postJson(route('leads.call.start',$lead))->assertOk();
  $this->post(route('leads.call.outcome',$lead),['outcome'=>'Unanswered','details'=>'','dashboard'=>1,'stay_dashboard'=>1,'request_key'=>(string)Str::uuid()])->assertRedirect(route('home'));
  $this->assertNull(session('active_lead_call_id'));
  $this->postJson(route('leads.call.start',$lead))->assertOk();
  $this->post(route('leads.call.outcome',$lead),['outcome'=>'Unanswered','details'=>'','dashboard'=>1,'request_key'=>(string)Str::uuid()])->assertRedirect(route('leads.show',[$lead,'activity'=>'Call log','call_filter'=>'outgoing']).'#lead-history');
  $this->postJson(route('leads.call.start',$lead))->assertOk();
  $this->post(route('leads.call.outcome',$lead),['outcome'=>'Unanswered','details'=>'Customer did not answer; retry after lunch.','request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
  $this->assertNull(session('active_lead_call_id'));
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'action'=>'Outgoing call: Unanswered','details'=>'Customer did not answer; retry after lunch.']);
  foreach(['Message sent','Message received'] as $direction){
   $this->post(route('leads.activities.store',$lead),['kind'=>'Call log','call_direction'=>$direction,'body'=>$direction.' body','request_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
   $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'action'=>$direction,'details'=>$direction.' body']);
  }
  $this->get(route('leads.show',[$lead,'activity'=>'Call log','call_filter'=>'message-sent']))->assertOk()->assertSee('Message sent body')->assertDontSee('Message received body')->assertSee('Message Received');
 }
 public function test_weightage_and_typed_activity(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Rama Steels','owner_id'=>$admin->id,'phone'=>'123','email'=>'buyer@example.test']);
  $this->get(route('leads.settings'))->assertOk();
  $this->post(route('leads.settings.save'),['section'=>'weights','version'=>1,'weights'=>['name'=>101]])->assertSessionHasErrors('weights.name');
  $this->post(route('leads.settings.save'),['section'=>'weights','version'=>1,'weights'=>['name'=>30,'phone'=>20,'email'=>50]])->assertSessionHasNoErrors();
  $this->getJson(route('leads.score',$lead))->assertOk()->assertJson(['score'=>100,'configured'=>true]);$this->assertSame(100,LeadSetting::find(1)->score($lead));$lead->email=null;$lead->save();$this->assertSame(50,LeadSetting::find(1)->score($lead));$this->getJson(route('leads.score',$lead))->assertOk()->assertJson(['score'=>50]);
  $page=$this->get(route('leads.show',$lead))->assertOk()->assertSee('RS')->assertSee('50 / 100')->assertSee('lead-followups-dropdown');
  $html=$page->getContent();
  $this->assertLessThan(strpos($html,'id="lead-followups"><summary>Follow-ups'),strpos($html,'lead-view-status"'));
  $this->assertLessThan(strpos($html,'class="lead-detail-grid"'),strpos($html,'id="lead-followups"><summary>Follow-ups'));
  $this->assertSame(1,substr_count($html,'id="lead-followups"'));
  $payload=['kind'=>'Customer note','body'=>'Needs delivery tomorrow','request_key'=>(string)Str::uuid()];
  $this->post(route('leads.activities.store',$lead),$payload)->assertSessionHasNoErrors();
  $this->post(route('leads.activities.store',$lead),$payload)->assertSessionHasNoErrors();
  $this->assertSame(1,$lead->activities()->where('kind','Customer note')->count());
  $this->get(route('leads.show',[$lead,'activity'=>'Customer note']))->assertSee('Needs delivery tomorrow');
  $this->get(route('leads.show',[$lead,'activity'=>'Note']))->assertDontSee('Needs delivery tomorrow');
  $role=Role::create(['name'=>'Read leads','permissions'=>['leads.view','leads.work','leads.activity']]);$user=User::create(['name'=>'Staff','email'=>'staff@example.test','password'=>'Password123','active'=>true,'role_id'=>$role->id]);$this->signIn($user);
  $this->getJson(route('leads.score',$lead))->assertNotFound();$this->get(route('leads.settings'))->assertForbidden();
  $this->post(route('leads.activities.store',$lead),$payload)->assertNotFound();
 }
 public function test_optional_weights_award_only_the_points_assigned_to_filled_fields(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Buyer','owner_id'=>$admin->id]);
  $this->postJson(route('leads.settings.save'),['section'=>'weights','version'=>1,'weights'=>['name'=>20,'email'=>40,'phone'=>'']])->assertOk()->assertJson(['saved'=>true,'version'=>2]);
  $this->getJson(route('leads.score',$lead))->assertOk()->assertJson(['score'=>20]);
  $this->assertSame(0,LeadSetting::find(1)->weights['phone']);
  $this->postJson(route('leads.settings.save'),['section'=>'weights','version'=>2,'weights'=>['name'=>100,'email'=>100]])->assertUnprocessable()->assertJsonValidationErrors('weights');
  $this->assertSame(60,array_sum(LeadSetting::find(1)->weights));
  $this->postJson(route('leads.settings.save'),['section'=>'weights','version'=>2,'weights'=>['name'=>5,'email'=>95]])->assertOk();
  $this->getJson(route('leads.score',$lead))->assertOk()->assertJson(['score'=>5]);
  $lead->update(['email'=>'buyer@example.test']);
  $this->getJson(route('leads.score',$lead))->assertOk()->assertJson(['score'=>100]);
  $this->postJson(route('leads.settings.save'),['section'=>'weights','version'=>3,'weights'=>['name'=>'','email'=>'']])->assertOk();
  $this->getJson(route('leads.score',$lead))->assertOk()->assertJson(['score'=>0]);
  $this->postJson(route('leads.settings.save'),['section'=>'weights','version'=>4])->assertOk();
  $this->assertSame(0,array_sum(LeadSetting::find(1)->weights));
 }
 public function test_email_is_sent_once_with_encrypted_settings(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Buyer','email'=>'buyer@example.test','owner_id'=>$admin->id]);
  $this->post(route('leads.settings.save'),['section'=>'email','version'=>1,'host'=>'smtp.example.test','port'=>587,'username'=>'sender','password'=>'super-secret','from_address'=>'sales@example.test','from_name'=>'Sales'])->assertSessionHasNoErrors();
  $this->assertStringNotContainsString('super-secret',DB::table('lead_settings')->value('email_password'));
  $this->get(route('leads.settings'))->assertDontSee('super-secret');
  Mail::fake();$payload=['kind'=>'Email','version'=>1,'subject'=>'Your inquiry','body'=>'Thank you for your inquiry.','request_key'=>(string)Str::uuid()];
  $this->post(route('leads.activities.store',$lead),$payload)->assertSessionHasNoErrors();
  $this->post(route('leads.activities.store',$lead),$payload)->assertSessionHasNoErrors();
  Mail::assertSent(\App\Mail\LeadEmail::class,1);
  $this->assertDatabaseHas('lead_activities',['kind'=>'Email','email_to'=>'buyer@example.test','delivery_status'=>'Sent']);
 }
 public function test_changing_smtp_username_requires_the_new_accounts_password(){
  $this->signIn();
  $settings=LeadSetting::findOrFail(1);
  $settings->email_config=['host'=>'smtp.gmail.com','port'=>587,'username'=>'old@example.test','from_address'=>'old@example.test','from_name'=>'Old'];
  $settings->email_password='old-app-password';$settings->save();
  $payload=['section'=>'email','version'=>$settings->version,'host'=>'smtp.gmail.com','port'=>587,'username'=>'new@example.test','from_address'=>'new@example.test','from_name'=>'New'];
  $this->post(route('leads.settings.save'),$payload)->assertSessionHasErrors('password');
  $this->assertSame('old@example.test',LeadSetting::findOrFail(1)->email_config['username']);
  $this->post(route('leads.settings.save'),$payload+['password'=>'new-app-password'])->assertSessionHasNoErrors();
  $this->assertSame('new-app-password',LeadSetting::findOrFail(1)->email_password);
 }
 public function test_import_sync_deduplication_and_webhook_auth(){
  $admin=$this->signIn();Http::preventStrayRequests();
  $connection=LeadConnection::where('provider','IndiaMART')->first();
  Http::fake(['mapi.indiamart.com/*'=>Http::response(['CODE'=>200,'RESPONSE'=>[['UNIQUE_QUERY_ID'=>'im-1','SENDER_NAME'=>'Imported Buyer','SENDER_MOBILE'=>'123','SENDER_COMPANY'=>'Steel buyer']]])]);
  $this->post(route('leads.connections.save',$connection),['enabled'=>1,'owner_id'=>$admin->id,'api_key'=>'test-api-secret'])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('leads',['source'=>'IndiaMART','external_id'=>'im-1']);
  $this->assertNull(Lead::where('external_id','im-1')->firstOrFail()->owner_id);
  $this->assertSame('Connected',$connection->fresh()->status);
  $this->assertStringNotContainsString('test-api-secret',DB::table('lead_connections')->where('id',$connection->id)->value('credentials'));
  $connection->update(['last_attempt_at'=>now()->subMinutes(16)]);LeadSync::run($connection);$this->assertSame(1,Lead::where('external_id','im-1')->count());
  $this->get(route('home'))->assertSee('Imported Buyer')->assertSee('IndiaMART');
  $jd=LeadConnection::where('provider','Justdial')->first();$jd->update(['enabled'=>true,'owner_id'=>$admin->id,'credentials'=>['webhook_secret'=>'test-webhook-key']]);
  $payload=['leadid'=>'jd-1','name'=>'JD Buyer','mobile'=>'456'];
  $this->postJson(route('leads.webhook',$jd),$payload)->assertUnauthorized();
  $this->withHeader('X-Lead-Key','test-webhook-key')->postJson(route('leads.webhook',$jd),$payload)->assertOk();
  $this->withHeader('X-Lead-Key','test-webhook-key')->postJson(route('leads.webhook',$jd),$payload)->assertOk()->assertJson(['created'=>false]);
  $this->assertSame(1,Lead::where('source','Justdial')->count());
  $this->get(route('leads.connections'))->assertOk()->assertDontSee('test-api-secret');
 }
 public function test_tradeindia_and_failed_sync_preserve_cursor(){
  $admin=$this->signIn();Http::preventStrayRequests();
  $connection=LeadConnection::where('provider','TradeIndia')->first();
  Http::fake(['www.tradeindia.com/*'=>Http::sequence()->push([['rfi_id'=>'ti-1','sender_name'=>'Trade Buyer','sender_mobile'=>'789','sender_email'=>'buyer@example.test']])->push(['error'=>'Denied'],401)]);
  $this->post(route('leads.connections.save',$connection),['enabled'=>1,'owner_id'=>$admin->id,'api_key'=>'ti-secret','user_id'=>'user-1','profile_id'=>'profile-1'])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('leads',['source'=>'TradeIndia','external_id'=>'ti-1']);
  $cursor=$connection->fresh()->synced_until->toDateTimeString();
  $connection->update(['last_attempt_at'=>now()->subMinutes(16)]);LeadSync::run($connection);
  $this->assertSame($cursor,$connection->fresh()->synced_until->toDateTimeString());
  $this->assertStringContainsString('unsuccessful',$connection->fresh()->status);
  $jd=LeadConnection::where('provider','Justdial')->first();$jd->update(['enabled'=>false,'credentials'=>['webhook_secret'=>'disabled-key'],'owner_id'=>$admin->id]);
  $this->withHeader('X-Lead-Key','disabled-key')->postJson(route('leads.webhook',$jd),['leadid'=>'bad','name'=>'Hidden'])->assertNotFound();
 }
 public function test_email_permission_and_transport_failure_are_not_reported_as_sent(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Buyer','owner_id'=>$admin->id,'email'=>'buyer@example.test']);
  LeadSetting::find(1)->update(['email_config'=>['host'=>'smtp.example.test','port'=>587,'username'=>'sender','from_address'=>'sales@example.test','from_name'=>'Sales'],'email_password'=>'secret']);
  Mail::shouldReceive('purge')->once();Mail::shouldReceive('mailer')->once()->andThrow(new \RuntimeException('SMTP 535 Authentication failed'));
  $this->post(route('leads.activities.store',$lead),['kind'=>'Email','version'=>1,'subject'=>'Hello','body'=>'Test','request_key'=>(string)Str::uuid()])->assertSessionHasErrors(['email'=>'SMTP login was rejected. Check the SMTP username and app password in Lead settings. Check your sent mailbox before trying again.']);
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'delivery_status'=>'Unconfirmed']);
  $role=Role::create(['name'=>'Activity only','permissions'=>['leads.view','leads.work','leads.activity']]);$user=User::create(['name'=>'Worker','email'=>'worker@example.test','password'=>'Password123','active'=>true,'role_id'=>$role->id]);$lead->update(['owner_id'=>$user->id]);$this->signIn($user);
  $this->post(route('leads.activities.store',$lead),['kind'=>'Email','version'=>1,'subject'=>'Hello','body'=>'Test','request_key'=>(string)Str::uuid()])->assertForbidden();
  $this->post(route('leads.settings.save'),['section'=>'weights','version'=>1,'weights'=>['name'=>100]])->assertForbidden();
 }
}
