<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Lead,LeadWorkItem,User,Role,CrmNotification};
use App\Support\LeadReminders;
class LeadManagementTest extends TestCase {
 public function test_job_roll_can_be_renamed_and_deleted_with_safe_lead_reassignment(){
  $admin=$this->signIn();
  $this->post(route('leads.job-roles.store'),['name'=>'Custom Mill'])->assertSessionHasNoErrors();
  $role=\App\Models\JobRole::where('name','Custom Mill')->firstOrFail();
  $replacement=\App\Models\JobRole::where('name','Scrap Suppliers')->firstOrFail();
  $lead=Lead::create(['name'=>'Role buyer','owner_id'=>$admin->id,'job_role'=>'Custom Mill','version'=>1]);
  $this->get(route('leads.job-roles'))->assertOk()->assertSee('Save name')->assertSee('Choose replacement roll')->assertSee('Delete');
  $this->put(route('leads.job-roles.update',$role),['name'=>'Custom Foundry'])->assertSessionHasNoErrors();
  $this->assertSame('Custom Foundry',$lead->fresh()->job_role);
  $this->assertSame(2,$lead->fresh()->version);
  $this->get(route('leads.create'))->assertOk()->assertSee('Custom Foundry');
  $this->delete(route('leads.job-roles.destroy',$role))->assertSessionHasErrors('replacement_id');
  $this->assertDatabaseHas('job_roles',['id'=>$role->id]);
  $this->delete(route('leads.job-roles.destroy',$role),['replacement_id'=>$replacement->id])->assertSessionHasNoErrors();
  $this->assertDatabaseMissing('job_roles',['id'=>$role->id]);
  $this->assertSame('Scrap Suppliers',$lead->fresh()->job_role);
  $this->assertSame(3,$lead->fresh()->version);
  $this->get(route('leads.create'))->assertOk()->assertDontSee('Custom Foundry');
  $this->post(route('leads.job-roles.store'),['name'=>'Unused roll'])->assertSessionHasNoErrors();
  $unused=\App\Models\JobRole::where('name','Unused roll')->firstOrFail();
  $this->delete(route('leads.job-roles.destroy',$unused))->assertSessionHasNoErrors();
  $this->assertDatabaseMissing('job_roles',['id'=>$unused->id]);
  $staff=$this->staff();$this->signIn($staff);
  $this->put(route('leads.job-roles.update',$replacement),['name'=>'Not allowed'])->assertForbidden();
  $this->delete(route('leads.job-roles.destroy',$replacement))->assertForbidden();
 }
 public function test_admin_created_job_roll_appears_in_lead_form_and_filter(){
  $admin=$this->signIn();
  $this->post(route('leads.job-roles.store'),['name'=>'Custom Steel Buyer'])->assertSessionHasNoErrors();
  $this->post(route('leads.job-roles.store'),['name'=>'Custom Steel Buyer'])->assertSessionHasErrors('name');
  $this->assertDatabaseHas('job_roles',['name'=>'Custom Steel Buyer']);
  $this->get(route('leads.create'))->assertOk()->assertSee('Custom Steel Buyer');
  $this->get(route('leads.index'))->assertOk()->assertSee('Custom Steel Buyer');
  $this->post(route('leads.store'),['name'=>'New buyer','job_role'=>'Custom Steel Buyer','stage'=>'New','priority'=>'Normal'])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('leads',['name'=>'New buyer','job_role'=>'Custom Steel Buyer']);
  $this->get(route('leads.index',['job_role'=>'Custom Steel Buyer']))->assertOk()->assertSee('New buyer');
  $staff=$this->staff();$this->signIn($staff);
  $this->get(route('leads.create'))->assertOk()->assertSee('Custom Steel Buyer');
  $this->post(route('leads.job-roles.store'),['name'=>'Unauthorized roll'])->assertForbidden();
 }
 public function test_unassigned_platform_lead_is_claimed_by_first_user_who_opens_it(){
  $admin=$this->signIn();$staff=$this->staff();
  $connection=\App\Models\LeadConnection::create(['provider'=>'Example feed','enabled'=>true,'owner_id'=>$admin->id]);
  $lead=Lead::create(['name'=>'New platform buyer','connection_id'=>$connection->id,'external_id'=>'feed-1','stage'=>'New','version'=>1]);
  $this->signIn($staff);
  $this->get(route('home'))->assertOk()->assertSee('Untouched')->assertSee('New platform buyer')->assertDontSee('id="platform-leads-title"',false);
  $this->get(route('leads.show',$lead))->assertOk();
  $this->assertSame($staff->id,$lead->fresh()->owner_id);
  $this->assertSame(1,$lead->activities()->where('action','Lead assigned')->count());
  $this->get(route('leads.show',$lead))->assertOk();
  $this->assertSame(1,$lead->activities()->where('action','Lead assigned')->count());
  $other=User::create(['name'=>'Another user','email'=>'another@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$staff->role_id]);
  $this->signIn($other);
  $this->get(route('leads.show',$lead))->assertNotFound();
  $this->assertSame($staff->id,$lead->fresh()->owner_id);
 }
 public function test_admin_can_track_who_opened_left_or_updated_a_lead(){
  $admin=$this->signIn();$staff=$this->staff();
  $lead=Lead::create(['name'=>'Tracked buyer','owner_id'=>$staff->id,'stage'=>'New','version'=>1]);
  $key=(string)\Illuminate\Support\Str::uuid();
  $this->signIn($staff);
  $this->post(route('leads.visit',$lead),['session_key'=>$key,'event'=>'opened'])->assertNoContent();
  $this->assertSame('Opened now',$lead->visits()->firstOrFail()->status());
  $this->post(route('leads.visit',$lead),['session_key'=>$key,'event'=>'closed'])->assertNoContent();
  $this->assertSame('Left without update',$lead->visits()->firstOrFail()->status());
  $this->post(route('leads.stage',$lead),['stage'=>'Negotiation','version'=>1,'visit_key'=>$key])->assertRedirectContains(route('leads.pipeline'));
  $this->assertSame('Stage updated',$lead->visits()->firstOrFail()->status());
  $other=Lead::create(['name'=>'Private buyer','owner_id'=>$admin->id]);
  $this->post(route('leads.visit',$other),['session_key'=>(string)\Illuminate\Support\Str::uuid(),'event'=>'opened'])->assertNotFound();
  $this->signIn($admin);
  $this->get(route('leads.index'))->assertOk()->assertSee('Sales user')->assertSee('Tracked buyer')->assertSee('data-lead-history-url',false);
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Lead activity status')->assertSee('Stage updated');
 }
 public function test_existing_lead_can_be_edited_and_soft_deleted_with_permission(){
  $admin=$this->signIn();
  $lead=Lead::create(['name'=>'Original lead','owner_id'=>$admin->id,'stage'=>'New','priority'=>'Normal','version'=>1]);
  $lead->activities()->create(['actor'=>$admin->name,'action'=>'Lead created']);
  $lead->workItems()->create(['kind'=>'Task','title'=>'Call buyer','due_at'=>now()->addDay(),'priority'=>'Normal','status'=>'Pending','version'=>1]);
  $this->get(route('leads.index'))->assertOk()->assertSee(route('leads.edit',$lead))->assertSee(route('leads.destroy',$lead));
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Edit lead')->assertSee('Delete lead');
  $this->put(route('leads.update',$lead),['name'=>'Updated lead','stage'=>'New','priority'=>'Normal','version'=>1])->assertSessionHasNoErrors()->assertRedirect(route('leads.index'));
  $lead=$lead->fresh();$this->assertSame('Updated lead',$lead->name);
  $this->delete(route('leads.destroy',$lead),['version'=>1])->assertSessionHasErrors('version');
  $this->assertNull($lead->fresh()->deleted_at);
  $this->delete(route('leads.destroy',$lead),['version'=>$lead->version])->assertRedirect(route('leads.index'));
  $this->assertSoftDeleted('leads',['id'=>$lead->id]);
  $this->get(route('leads.show',$lead->id))->assertNotFound();
  $this->get(route('leads.index'))->assertDontSee('Updated lead');
  $this->get(route('leads.work'))->assertDontSee('Call buyer');
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'action'=>'Lead created']);
  $this->assertDatabaseHas('lead_work_items',['lead_id'=>$lead->id,'title'=>'Call buyer']);
 }
 public function test_delete_requires_separate_permission_and_lead_visibility(){
  $admin=$this->signIn();$staff=$this->staff();
  $lead=Lead::create(['name'=>'Protected lead','owner_id'=>$staff->id,'version'=>1]);
  $this->signIn($staff);
  $this->delete(route('leads.destroy',$lead),['version'=>1])->assertForbidden();
  $this->assertNotNull($lead->fresh());
  $role=$staff->role;$role->permissions=['leads.view','leads.delete'];$role->save();
  $other=Lead::create(['name'=>'Other lead','owner_id'=>$admin->id,'version'=>1]);
  $this->delete(route('leads.destroy',$other),['version'=>1])->assertNotFound();
  $this->delete(route('leads.destroy',$lead),['version'=>1])->assertRedirect(route('leads.index'));
 }
 private function staff(){ $role=Role::create(['name'=>'Sales','permissions'=>['leads.view','leads.create','leads.edit','leads.stage','leads.tasks','leads.followups','leads.reminders']]);return User::create(['name'=>'Sales user','email'=>'sales@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]); }
 public function test_lead_lifecycle_permissions_and_pages(){
  $admin=$this->signIn();$staff=$this->staff();
  $this->post(route('leads.store'),['name'=>'Steel enquiry','company'=>'Buyer Ltd','job_role'=>'Rolling Mill / Manufacturer','stage'=>'New','priority'=>'High'])->assertSessionHasNoErrors()->assertRedirect();
  $lead=Lead::firstOrFail();
  foreach(['leads.index','leads.pipeline','leads.create','leads.work','home'] as $route)$this->get(route($route))->assertOk();
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Steel enquiry');
  $this->get(route('leads.edit',$lead))->assertOk();
  $this->post(route('leads.stage',$lead),['stage'=>'Negotiation','version'=>$lead->version])->assertSessionHasNoErrors()->assertRedirectContains(route('leads.pipeline'));
  $this->assertSame('Negotiation',$lead->fresh()->stage);
  $this->get(route('leads.pipeline',['highlight'=>$lead->id]))->assertOk()->assertSee('is-highlighted',false);
  $this->post(route('leads.stage',$lead),['stage'=>'Won','version'=>$lead->version])->assertSessionHasErrors('version');
  $this->post(route('leads.assign',$lead),['owner_id'=>$staff->id,'version'=>$lead->fresh()->version])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('crm_notifications',['user_id'=>$staff->id,'title'=>'Lead assigned: Steel enquiry']);
  $other=Lead::create(['name'=>'Private lead','owner_id'=>$admin->id]);
  $this->signIn($staff);
  $this->get(route('leads.show',$lead))->assertOk();
  $this->get(route('leads.show',$other))->assertNotFound();
  $this->get(route('leads.index'))->assertDontSee('Private lead');
  $this->get(route('home'))->assertDontSee('Private lead');
  $this->post(route('leads.stage',$other),['stage'=>'Won','version'=>1])->assertNotFound();
  $this->post(route('leads.assign',$lead),['owner_id'=>$admin->id,'version'=>$lead->fresh()->version])->assertSessionHasNoErrors()->assertRedirect(route('leads.index'));
  $this->get(route('leads.show',$lead))->assertNotFound();
  $this->signIn($admin);
  $this->get(route('leads.show',$lead))->assertOk();
 }
 public function test_job_roll_is_required_on_new_leads_and_filters_saved_leads(){
  $admin=$this->signIn();
  $this->get(route('leads.create'))->assertOk()->assertSee('Job Rolls *')->assertSee('Scrap Suppliers');
  $payload=['name'=>'Mill buyer','stage'=>'New','priority'=>'Normal'];
  $this->post(route('leads.store'),$payload)->assertSessionHasErrors('job_role');
  $this->post(route('leads.store'),$payload+['job_role'=>'Unknown'])->assertSessionHasErrors('job_role');
  $this->post(route('leads.store'),$payload+['job_role'=>'Rolling Mill / Manufacturer'])->assertSessionHasNoErrors();
  Lead::create(['name'=>'Scrap buyer','owner_id'=>$admin->id,'job_role'=>'Scrap Suppliers']);
  $this->assertDatabaseHas('leads',['name'=>'Mill buyer','job_role'=>'Rolling Mill / Manufacturer']);
  $this->get(route('leads.index',['job_role'=>'Scrap Suppliers']))->assertOk()->assertSee('Scrap buyer')->assertDontSee('Mill buyer')->assertSee('data-auto-lead-filters',false)->assertDontSee('>Filter</button>',false);
  $this->get(route('leads.index',['job_role'=>'Rolling Mill / Manufacturer']))->assertOk()->assertSee('Mill buyer')->assertDontSee('Scrap buyer');
 }
 public function test_visible_lead_owner_can_transfer_to_another_authorized_user(){
  $admin=$this->signIn();
  $viewRole=Role::create(['name'=>'Lead viewers','permissions'=>['leads.view']]);
  $workRole=Role::create(['name'=>'Lead managers','permissions'=>['leads.view','leads.edit','leads.stage','leads.tasks','leads.followups','leads.reminders','leads.activity']]);
  $sender=User::create(['name'=>'Sender','email'=>'sender@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$viewRole->id]);
  $receiver=User::create(['name'=>'Receiver','email'=>'receiver@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$workRole->id]);
  $ineligibleRole=Role::create(['name'=>'No lead access','permissions'=>[]]);
  $ineligible=User::create(['name'=>'Ineligible','email'=>'ineligible@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$ineligibleRole->id]);
  $lead=Lead::create(['name'=>'Transfer test','owner_id'=>$sender->id]);
  $outsider=Lead::create(['name'=>'Private test','owner_id'=>$admin->id]);
  $this->signIn($sender);
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Transfer this lead')->assertSee('Receiver');
  $this->get(route('home'))->assertSee('Leads');
  $this->post(route('leads.assign',$outsider),['owner_id'=>$receiver->id,'version'=>$outsider->fresh()->version])->assertNotFound();
  $this->post(route('leads.assign',$lead),['owner_id'=>$ineligible->id,'version'=>$lead->fresh()->version])->assertSessionHasErrors('owner_id');
  $this->post(route('leads.assign',$lead),['owner_id'=>$receiver->id,'version'=>0])->assertSessionHasErrors('version');
  $this->post(route('leads.assign',$lead),['owner_id'=>$receiver->id,'version'=>$lead->fresh()->version])->assertRedirect(route('leads.index'));
  $this->assertSame($receiver->id,$lead->fresh()->owner_id);
  $this->get(route('leads.show',$lead))->assertNotFound();
  $this->signIn($receiver);
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Transfer this lead');
  $this->post(route('leads.stage',$lead),['stage'=>'Negotiation','version'=>$lead->fresh()->version])->assertSessionHasNoErrors();
  $this->assertSame('Negotiation',$lead->fresh()->stage);
  $this->post(route('leads.activities.store',$lead),['kind'=>'Note','body'=>'Buyer needs a revised quote','request_key'=>(string)\Illuminate\Support\Str::uuid()])->assertSessionHasNoErrors();
  $this->get(route('leads.show',$lead))->assertSee('Buyer needs a revised quote');
 }
 public function test_bulk_transfer_and_admin_view_status_from_lead_page(){
  $admin=$this->signIn();
  $role=Role::create(['name'=>'Lead team','permissions'=>['leads.view','leads.activity']]);
  $first=User::create(['name'=>'First rep','email'=>'first@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $second=User::create(['name'=>'Second rep','email'=>'second@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $a=Lead::create(['name'=>'Buyer A','owner_id'=>$first->id]);
  $b=Lead::create(['name'=>'Buyer B','owner_id'=>$first->id]);
  $this->signIn($first);
  $this->get(route('leads.index'))->assertOk()->assertSee('Select Buyer A')->assertSee('Select Buyer B');
  $this->get(route('leads.show',$a))->assertOk();
  $this->post(route('leads.transfer'),['lead_ids'=>[$a->id,$b->id],'owner_id'=>$second->id,'versions'=>[$a->id=>99,$b->id=>$b->fresh()->version]])->assertSessionHasErrors('version');
  $this->assertSame($first->id,$a->fresh()->owner_id);
  $this->assertSame($first->id,$b->fresh()->owner_id);
  $this->post(route('leads.transfer'),['lead_ids'=>[$a->id,$b->id],'owner_id'=>$second->id,'versions'=>[$a->id=>$a->fresh()->version,$b->id=>$b->fresh()->version]])->assertRedirect(route('leads.index'));
  $this->assertSame($second->id,$a->fresh()->owner_id);
  $this->assertSame($second->id,$b->fresh()->owner_id);
  $this->get(route('leads.show',$a))->assertNotFound();
  $this->signIn($second);
  $this->get(route('leads.show',$a))->assertOk();
  $this->post(route('leads.visit',$a),['session_key'=>(string)\Illuminate\Support\Str::uuid(),'event'=>'opened'])->assertNoContent();
  $this->post(route('leads.activities.store',$a),['kind'=>'Note','body'=>'Discussed requirement','request_key'=>(string)\Illuminate\Support\Str::uuid()])->assertSessionHasNoErrors();
  $this->post(route('leads.stage',$a),['stage'=>'Negotiation','version'=>$a->fresh()->version])->assertForbidden();
  $this->post(route('leads.work.store',$a),['kind'=>'Task','title'=>'Call buyer','due_at'=>now()->addDay()->format('Y-m-d\TH:i'),'priority'=>'Normal'])->assertForbidden();
  $this->signIn($admin);
  $this->get(route('leads.index'))->assertOk()->assertSee('Assigned to')->assertSee('Second rep')->assertSee('data-lead-history-url',false);
  $this->get(route('home'))->assertOk()->assertDontSee('Opened by team')->assertDontSee('Last transfer');
  $this->get(route('leads.show',$a))->assertOk()->assertSee('Lead activity status')->assertSee('Second rep')->assertSee('Opened now');
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$a->id,'action'=>'Lead viewed','details'=>'user:'.$first->id,'actor'=>'First rep']);
  $this->assertSame(2,\App\Models\LeadActivity::where('action','Lead assigned')->count());
 }
 public function test_work_reminders_and_rescheduling(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Buyer','owner_id'=>$admin->id]);
  $data=['kind'=>'Follow-up','title'=>'Discuss quotation','priority'=>'High','due_at'=>now()->subMinute()->format('Y-m-d\TH:i')];
  $this->post(route('leads.work.store',$lead),$data)->assertSessionHasNoErrors();$item=LeadWorkItem::firstOrFail();
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Discuss quotation');
  $this->get(route('leads.work'))->assertOk()->assertSee('Discuss quotation');
  $this->assertSame(1,LeadReminders::deliver());$this->assertSame(0,LeadReminders::deliver());
  $this->post(route('leads.work.status',$item),['status'=>'Completed','outcome'=>'Called buyer','version'=>1])->assertSessionHasNoErrors();
  $this->assertNotNull($item->fresh()->completed_at);
  $this->post(route('leads.work.status',$item),['status'=>'Pending','version'=>1])->assertSessionHasErrors('version');
  $this->put(route('leads.work.update',$item),array_merge($data,['version'=>2,'due_at'=>now()->addDay()->format('Y-m-d\TH:i')]))->assertSessionHasNoErrors();
  $this->post(route('leads.work.status',$item),['status'=>'Pending','version'=>3])->assertSessionHasNoErrors();
  $this->assertSame(0,LeadReminders::deliver());
  $this->signIn($this->staff());
  $this->post(route('leads.work.status',$item),['status'=>'Completed','version'=>4])->assertNotFound();
  $this->get(route('leads.work'))->assertDontSee('Discuss quotation');
 }
 public function test_pipeline_and_dashboard_use_bounded_pages(){
  $admin=$this->signIn();
  for($i=1;$i<=12;$i++) Lead::create(['name'=>'Paged lead '.$i,'owner_id'=>$admin->id,'stage'=>'New','contact_saved_at'=>now()]);
  $response=$this->get(route('leads.pipeline'))->assertOk();
  $response->assertViewHas('columns',fn($columns)=>$columns['New']->count()===10 && $columns['New']->total()===12);
  $this->get(route('leads.pipeline',['stage_page_0'=>2]))->assertOk()->assertViewHas('columns',fn($columns)=>$columns['New']->count()===2);
  $this->get(route('home'))->assertOk()->assertSee('Total Leads')->assertSee('Call Details')->assertSee('Contacts')->assertSee('data-contacts-expand',false)->assertViewHas('recentLeads',fn($leads)=>$leads->count()===12 && $leads->total()===12);
  $this->get(route('home',['filters'=>['name'=>'Paged lead 12']]))->assertOk()->assertViewHas('recentLeads',fn($leads)=>$leads->total()===1);
  $this->get(route('home',['filters'=>['stage'=>'Negotiation']]))->assertOk()->assertViewHas('recentLeads',fn($leads)=>$leads->total()===0);
  $this->get(route('home',['filters'=>['followups'=>0]]))->assertOk()->assertViewHas('recentLeads',fn($leads)=>$leads->total()===12);
  $this->get(route('home'))->assertDontSee('Search contacts')->assertSee('data-auto-contact-filters',false);
 }
}
