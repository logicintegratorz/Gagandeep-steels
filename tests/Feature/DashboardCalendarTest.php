<?php
namespace Tests\Feature;

use App\Models\{Lead,LeadActivity,LeadVisit,LeadWorkItem,Role,User};
use Tests\TestCase;

final class DashboardCalendarTest extends TestCase {
 public function test_calendar_shows_assigned_work_saved_contacts_and_activity_with_user_filter(){
  $admin=$this->signIn();
  $staffRole=Role::create(['name'=>'Calendar staff','permissions'=>['leads.view','leads.tasks','leads.activity']]);
  $staff=User::create(['name'=>'Calendar Staff','email'=>'calendar@example.test','password'=>'Password123','active'=>true,'role_id'=>$staffRole->id]);
  $lead=Lead::create(['name'=>'Buyer One','owner_id'=>$staff->id,'created_by'=>$admin->id,'contact_saved_at'=>now()]);
  LeadWorkItem::create(['lead_id'=>$lead->id,'kind'=>'Task','title'=>'Call buyer','due_at'=>now()->addHour(),'assigned_to'=>$staff->id,'status'=>'Pending']);
  LeadActivity::create(['lead_id'=>$lead->id,'actor'=>$staff->name,'action'=>'Called customer','details'=>'Asked for prices','kind'=>'Call log']);
  LeadVisit::create(['lead_id'=>$lead->id,'user_id'=>$staff->id,'session_key'=>(string)\Illuminate\Support\Str::uuid(),'opened_at'=>now(),'last_seen_at'=>now()]);
  $this->post(route('leads.settings.save'),['section'=>'weights','version'=>1,'weights'=>['name'=>10]])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('crm_audit_events',['user_id'=>$admin->id,'route_name'=>'leads.settings.save']);
  $otherLead=Lead::create(['name'=>'Admin buyer','owner_id'=>$admin->id,'created_by'=>$admin->id]);
  LeadWorkItem::create(['lead_id'=>$otherLead->id,'kind'=>'Task','title'=>'Review admin buyer','due_at'=>now()->addHour(),'assigned_to'=>$staff->id,'status'=>'Pending']);
  $range=['start'=>now()->subDay()->toDateString(),'end'=>now()->addDay()->toDateString()];
  $dashboard=$this->get(route('home'))->assertOk()->assertSee('Work calendar')->assertSee('data-calendar-view="month"',false)->assertSee('data-calendar-user',false)->assertSee('data-calendar-all checked',false)->assertDontSee('value="Assignment" data-calendar-kind',false)->assertDontSee('value="Contact" data-calendar-kind',false)->assertSee(route('leads.index',['view'=>'extended']))
   ->assertSee('Contacts')->assertSee('Add New Lead')->assertSee('Extended View')->assertSee('Pipeline');
  $html=$dashboard->getContent();
  $this->assertLessThan(strpos($html,'id="dashboard-calendar"'),strpos($html,'class="dash-contacts"'));
  $this->get(route('leads.index',['view'=>'extended']))->assertOk()->assertSee('class="extended-leads"',false)->assertSee('extended-sidebar-edge')->assertSee('aria-label="Open navigation"',false)->assertSee('extended-leads.js');
  $this->get(route('leads.index'))->assertOk()->assertDontSee('class="extended-leads"',false)->assertDontSee('extended-sidebar-edge');
  $this->get(route('leads.index',['view'=>'extended','q'=>'Buyer']))->assertOk()->assertSee('name="view" value="extended"',false);
  $this->getJson(route('dashboard.calendar.leads',['q'=>'Buyer']))->assertOk()->assertSee('Buyer One');
  $this->getJson(route('dashboard.calendar',$range))->assertOk()->assertSee('Call buyer')->assertSee('Saved as contact')->assertSee('Called customer')->assertSee('Opened lead: Buyer One');
  $this->getJson(route('dashboard.calendar',$range+['user_id'=>$admin->id]))->assertOk()->assertDontSee('Call buyer')->assertDontSee('Called customer')->assertDontSee('Lead added: Buyer One');
  $this->signIn($staff);
  $this->getJson(route('dashboard.calendar.leads',['q'=>'Buyer']))->assertForbidden();
  $this->getJson(route('dashboard.calendar',$range+['user_id'=>$admin->id]))->assertOk()->assertSee('Call buyer')->assertSee('Review admin buyer')->assertSee('Lead added: Buyer One');
 }
 public function test_admin_assignments_appear_on_recipient_calendar_and_stay_private(){
  $admin=$this->signIn();
  $role=Role::create(['name'=>'Assigned staff','permissions'=>['leads.view','leads.tasks']]);
  $staff=User::create(['name'=>'Assigned Staff','email'=>'assigned@example.test','password'=>'Password123','active'=>true,'role_id'=>$role->id]);
  $lead=Lead::create(['name'=>'Assigned buyer','owner_id'=>$admin->id,'created_by'=>$admin->id]);
  $this->post(route('leads.assign',$lead),['owner_id'=>$staff->id,'version'=>$lead->fresh()->version])->assertSessionHasNoErrors();
  $range=['start'=>now()->subDay()->toDateString(),'end'=>now()->addDay()->toDateString()];
  $this->assertDatabaseHas('calendar_assignments',['recipient_id'=>$staff->id,'title'=>'Lead assigned: Assigned buyer']);
  $this->getJson(route('dashboard.calendar',$range+['user_id'=>$staff->id]))->assertOk()->assertSee('Lead assigned: Assigned buyer');
  $this->getJson(route('dashboard.calendar',$range+['user_id'=>$admin->id]))->assertOk()->assertDontSee('Lead assigned: Assigned buyer');
  $this->signIn($staff);
  $this->getJson(route('dashboard.calendar',$range+['user_id'=>$admin->id]))->assertOk()->assertSee('Lead assigned: Assigned buyer');
 }
}
