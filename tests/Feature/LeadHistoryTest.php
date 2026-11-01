<?php
namespace Tests\Feature;

use App\Models\{Lead,Role,User};
use Tests\TestCase;

class LeadHistoryTest extends TestCase {
 public function test_first_open_claims_untouched_lead_exclusively_and_admin_can_review_history(){
  $admin=$this->signIn();
  $role=Role::create(['name'=>'Lead reps','permissions'=>['leads.view','leads.activity','leads.tasks']]);
  $first=User::create(['name'=>'First rep','email'=>'first@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $second=User::create(['name'=>'Second rep','email'=>'second@example.test','password'=>'TestPassword123','active'=>true,'role_id'=>$role->id]);
  $lead=Lead::create(['name'=>'Untouched buyer','stage'=>'New','version'=>1]);
  $this->signIn($first);
  $this->get(route('leads.show',$lead))->assertOk();
  $this->assertSame($first->id,$lead->fresh()->owner_id);
  $this->assertDatabaseHas('lead_activities',['lead_id'=>$lead->id,'actor_id'=>$first->id,'action'=>'Lead assigned']);
  $this->get(route('leads.history',$lead))->assertForbidden();
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Transfer this lead')->assertSee('<optgroup label="Lead reps">',false);
  $this->post(route('leads.assign',$lead),['owner_id'=>$second->id,'version'=>$lead->fresh()->version])->assertSessionHasNoErrors();
  $this->assertSame($second->id,$lead->fresh()->owner_id);
  $this->get(route('leads.index'))->assertOk()->assertDontSee('Untouched buyer');
  $this->get(route('leads.show',$lead))->assertNotFound();
  $this->signIn($second);
  $this->get(route('leads.index'))->assertOk()->assertSee('Untouched buyer');
  $this->get(route('leads.show',$lead))->assertOk();
  $this->assertSame(2,$lead->activities()->where('action','Lead assigned')->count());
  $this->signIn($admin);
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('Transfer this lead');
  $this->post(route('leads.assign',$lead),['owner_id'=>$first->id,'version'=>$lead->fresh()->version])->assertSessionHasNoErrors();
  $this->assertSame($first->id,$lead->fresh()->owner_id);
  $this->post(route('leads.work.store',$lead),['kind'=>'Task','title'=>'Call buyer','due_at'=>now()->addDay()->format('Y-m-d\TH:i'),'priority'=>'Normal','assigned_to'=>$first->id])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('calendar_assignments',['lead_id'=>$lead->id,'kind'=>'Task','recipient_id'=>$first->id]);
  $this->get(route('leads.index'))->assertOk()->assertSee('data-lead-history-url',false);
  $this->get(route('home'))->assertOk()->assertSee('Untouched buyer');
  $this->get(route('leads.pipeline'))->assertOk()->assertSee('data-lead-history-url',false);
  $this->get(route('leads.show',$lead))->assertOk()->assertSee('data-lead-history-url',false);
  $this->get(route('leads.history',$lead))->assertOk()->assertSee('Lead created')->assertSee('Lead claimed automatically')->assertSee('Task assigned')->assertSee('First rep')->assertSee('Lead transferred');
  $this->get(route('leads.history',[$lead,'user'=>$first->id]))->assertOk()->assertSee('Lead claimed automatically')->assertSee('Task assigned');
 }
}
