<?php
namespace Tests\Feature;

use App\Models\{Lead,LeadConnection,Role,User};
use App\Support\LeadImport;
use Tests\TestCase;

class UntouchedLeadFlowTest extends TestCase {
 public function test_won_stage_automatically_saves_unsaved_lead_as_contact(){
  $role=Role::create(['name'=>'Stage editor','permissions'=>['leads.view','leads.stage']]);
  $user=User::create(['name'=>'Stage editor','email'=>'stage-editor@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $this->signIn($user);
  $lead=Lead::create(['name'=>'Won buyer','owner_id'=>$user->id,'stage'=>'Negotiation','version'=>1]);
  $this->assertNull($lead->contact_saved_at);
  $this->post(route('leads.stage',$lead),['stage'=>'Won','version'=>1])->assertSessionHasNoErrors()->assertRedirectContains(route('leads.pipeline'));
  $lead->refresh();
  $this->assertSame('Won',$lead->stage);
  $this->assertNotNull($lead->contact_saved_at);
  $this->assertSame(1,$lead->activities()->where('action','Saved as contact')->count());
  $this->get(route('home'))->assertSee('Won buyer');
 }
 public function test_claimed_lead_can_be_transferred_by_owner_and_only_admin_can_save_contact(){
  $admin=$this->signIn();
  $role=Role::create(['name'=>'Lead operator','permissions'=>['leads.view','leads.edit','leads.delete']]);
  $first=User::create(['name'=>'First operator','email'=>'first-operator@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $second=User::create(['name'=>'Second operator','email'=>'second-operator@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $lead=Lead::create(['name'=>'New inquiry','source'=>'IndiaMART']);
  $this->get(route('leads.index'))->assertOk()->assertDontSee('New inquiry');
  $this->signIn($first);
  $this->get(route('home'))->assertOk()->assertSee('Untouched')->assertSee('New inquiry');
  $this->get(route('leads.index'))->assertOk()->assertDontSee('New inquiry');
  $this->get(route('leads.show',$lead))->assertOk();
  $this->assertSame($first->id,$lead->fresh()->claimed_by);
  $this->assertNull($lead->fresh()->contact_saved_at);
  $this->get(route('leads.index'))->assertOk()->assertSee('New inquiry');
  $this->get(route('home'))->assertOk()->assertSee('New inquiry')->assertSee('Assigned lead');
  $this->post(route('leads.contact',$lead))->assertForbidden();
  $this->post(route('leads.assign',$lead),['owner_id'=>$second->id,'version'=>$lead->fresh()->version])->assertSessionHasNoErrors();
  $this->assertSame($second->id,$lead->fresh()->owner_id);
  $this->assertSame($first->id,$lead->fresh()->claimed_by);
  $this->signIn($second);
  $this->get(route('leads.show',$lead))->assertOk();
  $this->signIn($admin);
  $this->get(route('leads.index'))->assertOk()->assertSee('New inquiry');
  $this->post(route('leads.contact',$lead))->assertSessionHasNoErrors();
  $this->assertNotNull($lead->fresh()->contact_saved_at);
  $this->get(route('home'))->assertSee('New inquiry');
 }

 public function test_date_range_archives_only_untouched_leads_and_later_import_finds_archived_history(){
  $admin=$this->signIn();
  $connection=LeadConnection::where('provider','IndiaMART')->firstOrFail();$connection->update(['enabled'=>true,'owner_id'=>$admin->id]);
  $other=LeadConnection::where('provider','Justdial')->firstOrFail();$other->update(['enabled'=>true,'owner_id'=>$admin->id]);
  LeadImport::ingest($connection,['UNIQUE_QUERY_ID'=>'im-10','SENDER_NAME'=>'Buyer A','SENDER_MOBILE'=>'919876543210','SENDER_COMPANY'=>'A Steel','SENDER_CITY'=>'Delhi']);
  $old=Lead::where('external_id','im-10')->firstOrFail();
  $old->activities()->create(['actor'=>'Administrator','action'=>'Quotation discussed','details'=>'Price quote 100 per tonne']);
  $saved=Lead::create(['name'=>'Saved contact','owner_id'=>$admin->id,'contact_saved_at'=>now()]);
  $claimed=Lead::create(['name'=>'Assigned inquiry','owner_id'=>$admin->id,'claimed_by'=>$admin->id,'claimed_at'=>now()]);
  $yesterday=Lead::create(['name'=>'Older untouched','created_at'=>now()->subDays(2)]);
  $today=now('Asia/Kolkata')->format('Y-m-d');
  $this->post(route('leads.untouched.delete'),['from'=>$today,'to'=>$today])->assertRedirect(route('home'));
  $this->assertSoftDeleted($old);
  $this->assertNotNull($saved->fresh());
  $this->assertNotNull($claimed->fresh());
  $this->assertNotNull($yesterday->fresh());
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$old->id,'action'=>'Quotation discussed']);
  LeadImport::ingest($other,['leadid'=>'jd-20','name'=>'Buyer A','mobile'=>'9876543210','company'=>'A Steel','city'=>'Delhi']);
  $new=Lead::where('external_id','jd-20')->firstOrFail();
  $this->assertSame(1,$new->repeatMatches()->count());
  $this->assertSame($old->id,$new->repeatMatches()->first()->previous_lead_id);
  $this->get(route('home'))->assertSee('Received again');
  $this->get(route('leads.show',$new))->assertSee('Received before')->assertSee('View earlier history');
  $this->get(route('leads.history',$old->id))->assertOk()->assertSee('Archived lead')->assertSee('Quotation discussed')->assertSee('Price quote 100 per tonne');
  $new->delete();
  LeadImport::ingest($connection,['UNIQUE_QUERY_ID'=>'im-10','SENDER_NAME'=>'Buyer A','SENDER_MOBILE'=>'9876543210','SENDER_COMPANY'=>'A Steel','SENDER_CITY'=>'Delhi']);
  $third=Lead::where('connection_id',$connection->id)->where('external_id','im-10')->firstOrFail();
  $this->assertSame(2,$third->repeatMatches()->count());
 }
}
