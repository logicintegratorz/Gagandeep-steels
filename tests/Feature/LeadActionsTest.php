<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Lead,LeadActivity,LeadWorkItem,LeadSetting,Role,User};
use App\Support\LeadReminders;
class LeadActionsTest extends TestCase {
 public function test_activity_edits_delete_and_contact_conversion(){
  $admin=$this->signIn();$lead=Lead::create(['name'=>'Imported buyer','owner_id'=>$admin->id,'source'=>'IndiaMART']);
  $activity=$lead->activities()->create(['actor'=>'Admin','action'=>'Note title','details'=>'Old','kind'=>'Note']);
  $this->put(route('leads.activities.update',[$lead,$activity]),['version'=>1,'subject'=>'Corrected note','body'=>'Updated'])->assertSessionHasNoErrors();
  $this->assertSame('Updated',$activity->fresh()->details);
  $this->delete(route('leads.activities.destroy',[$lead,$activity]),['version'=>1])->assertSessionHasErrors('version');
  $this->delete(route('leads.activities.destroy',[$lead,$activity]),['version'=>2])->assertSessionHasNoErrors();
  $this->assertSoftDeleted('lead_activities',['id'=>$activity->id]);
  $this->post(route('leads.contact',$lead))->assertSessionHasNoErrors();$this->post(route('leads.contact',$lead))->assertSessionHasNoErrors();
  $this->assertNotNull($lead->fresh()->contact_saved_at);$this->assertSame(1,$lead->activities()->where('action','Saved as contact')->count());
  $this->get(route('home',['filters'=>['name'=>'Imported buyer']]))->assertOk()->assertSee('Imported buyer');
  $this->postJson(route('leads.settings.save'),['section'=>'weights','version'=>1,'weights'=>['name'=>100]])->assertOk()->assertJson(['saved'=>true,'version'=>2]);
  $this->getJson(route('leads.score',$lead))->assertJson(['score'=>100]);
 }
 public function test_admin_assigns_work_independently_and_reminder_reaches_assignee(){
  $admin=$this->signIn();$role=Role::create(['name'=>'Worker','permissions'=>['leads.view','leads.tasks']]);$worker=User::create(['name'=>'Assigned worker','email'=>'worker@test.example','password'=>'Password123','active'=>true,'role_id'=>$role->id]);
  $lead=Lead::create(['name'=>'Admin lead','owner_id'=>$admin->id]);
  $data=['kind'=>'Task','title'=>'Call the buyer','priority'=>'Normal','due_at'=>now()->subMinute()->format('Y-m-d\TH:i'),'assigned_to'=>$worker->id];
  $this->post(route('leads.work.store',$lead),$data)->assertSessionHasNoErrors();$item=LeadWorkItem::firstOrFail();$this->assertSame($worker->id,$item->assigned_to);
  $this->assertSame(2,LeadReminders::deliver($admin->id));$this->assertSame(0,LeadReminders::deliver($worker->id));
  $this->assertDatabaseHas('crm_notifications',['user_id'=>$admin->id,'title'=>'Overdue Task: Call the buyer']);
  $this->assertDatabaseHas('crm_notifications',['user_id'=>$worker->id,'title'=>'Overdue Task: Call the buyer']);
  $this->signIn($worker);$this->get(route('leads.work'))->assertOk()->assertSee('Call the buyer');
  $this->post(route('leads.work.status',$item),['version'=>1,'status'=>'Completed'])->assertSessionHasNoErrors();
  $this->put(route('leads.work.update',$item),array_merge($data,['version'=>2,'kind'=>'Reminder']))->assertForbidden();
  $this->put(route('leads.work.update',$item),$data+['version'=>2])->assertSessionHasNoErrors();
  $this->post(route('leads.work.store',$lead),$data)->assertForbidden();
  $this->put(route('leads.work.update',$item),array_merge($data,['version'=>3,'assigned_to'=>$admin->id]))->assertForbidden();
 }
}
