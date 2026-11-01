<?php

namespace App\Http\Controllers;

use App\Models\{Lead, LeadGroup, LeadWorkItem, User};
use App\Support\LeadWhatsAppLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class DashboardBulkLeadController
{
    private const ACTIONS=['call','email','whatsapp','sms','reminder','note','opportunity','contact','list','user','stage','category','delete','feedback'];

    public function __invoke(Request $request)
    {
        $base=$request->validate(['action'=>['required',Rule::in(self::ACTIONS)],'lead_ids'=>'required|array|min:1|max:100','lead_ids.*'=>'integer|distinct']);
        $this->authorizeAction($request,$base['action']);
        $ids=array_map('intval',$base['lead_ids']);
        $leads=Lead::visibleTo($request->user())->whereIn('id',$ids)->orderBy('id')->get();
        abort_unless($leads->count()===count($ids),404);
        $data=$this->validateAction($request,$base['action']);$links=[];

        DB::transaction(function()use($request,$leads,$base,$data,&$links){
            $locked=Lead::whereIn('id',$leads->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            foreach($locked as $lead){
                abort_unless(Lead::visibleTo($request->user())->whereKey($lead->id)->exists(),404);
                $this->apply($request,$lead,$base['action'],$data,$links);
            }
        });

        $linkAction=in_array($base['action'],['call','email','whatsapp','sms'],true);
        return response()->json(['message'=>$linkAction?count($links).' of '.$leads->count().' contact action(s) prepared. Open each item below.':$leads->count().' '.($leads->count()===1?'lead':'leads').' updated successfully.','links'=>$links,'refresh'=>!$linkAction]);
    }

    private function authorizeAction(Request $request,string $action):void
    {
        $permission=match($action){
            'email'=>'leads.email',
            'call','whatsapp','sms','note'=>'leads.activity',
            'reminder'=>'leads.reminders',
            'feedback'=>'leads.followups',
            'opportunity','stage'=>'leads.stage',
            'contact','category'=>'leads.edit',
            'list'=>'leads.lists',
            'user'=>'leads.assign',
            'delete'=>'leads.delete',
        };
        abort_unless($request->user()->allows($permission),403);
    }

    private function validateAction(Request $request,string $action):array
    {
        return match($action){
            'call'=>$request->validate(['details'=>'nullable|string|max:10000']),
            'email'=>$request->validate(['subject'=>'required|string|max:255','message'=>'required|string|max:20000']),
            'whatsapp','sms'=>$request->validate(['message'=>'required|string|max:2000']),
            'reminder','feedback'=>$request->validate(['title'=>'required|string|max:255','due_at'=>'required|date_format:Y-m-d\TH:i','details'=>'nullable|string|max:10000']),
            'note'=>$request->validate(['title'=>'nullable|string|max:255','details'=>'required|string|max:20000']),
            'opportunity'=>$request->validate(['stage'=>['required',Rule::in(array_slice(Lead::STAGES,1,5))],'value'=>'nullable|numeric|min:0|max:9999999999999.99','details'=>'nullable|string|max:10000']),
            'list'=>$request->validate(['group_id'=>'required|integer|exists:lead_groups,id']),
            'user'=>$request->validate(['owner_id'=>'required|integer|exists:users,id']),
            'stage'=>$request->validate(['stage'=>['required',Rule::in(Lead::STAGES)]]),
            'category'=>$request->validate(['category'=>'required|string|max:100']),
            'delete'=>$request->validate(['confirmation'=>['required',Rule::in(['DELETE'])]]),
            default=>[],
        };
    }

    private function apply(Request $request,Lead $lead,string $action,array $data,array &$links):void
    {
        match($action){
            'call'=>$this->prepareLink($request,$lead,'Call',$this->phoneLink($lead),$data['details']??null,$links),
            'email'=>$this->prepareLink($request,$lead,'Email',filter_var($lead->email,FILTER_VALIDATE_EMAIL)?'mailto:'.$lead->email.'?subject='.rawurlencode($data['subject']).'&body='.rawurlencode($data['message']):null,$data['subject'].' · '.$data['message'],$links),
            'whatsapp'=>$this->prepareLink($request,$lead,'WhatsApp',LeadWhatsAppLink::for($lead,$data['message']),$data['message'],$links),
            'sms'=>$this->prepareLink($request,$lead,'SMS',$this->smsLink($lead,$data['message']),$data['message'],$links),
            'reminder'=>$this->work($request,$lead,'Reminder',$data),
            'feedback'=>$this->work($request,$lead,'Follow-up',$data),
            'note'=>$this->record($request,$lead,'Note',$data['title']?:'Note',$data['details']),
            'opportunity'=>$this->opportunity($request,$lead,$data),
            'contact'=>$this->contact($request,$lead),
            'list'=>$this->list($request,$lead,$data['group_id']),
            'user'=>$this->owner($request,$lead,$data['owner_id']),
            'stage'=>$this->stage($request,$lead,$data['stage']),
            'category'=>$this->category($request,$lead,$data['category']),
            'delete'=>$this->delete($request,$lead),
        };
    }

    private function record(Request $request,Lead $lead,string $kind,string $action,?string $details=null):void
    {$lead->activities()->create(['actor'=>$request->user()->name,'actor_id'=>$request->user()->id,'kind'=>$kind,'action'=>$action,'details'=>$details]);}

    private function prepareLink(Request $request,Lead $lead,string $channel,?string $url,?string $details,array &$links):void
    {if(!$url)return;$this->record($request,$lead,$channel==='Call'?'Call log':'Note',$channel.' action prepared',$details);$links[]=['name'=>$lead->name,'channel'=>$channel,'url'=>$url];}

    private function phoneLink(Lead $lead):?string{$number=preg_replace('/[^\d+]/','',(string)$lead->phone);return $number?'tel:'.$number:null;}
    private function smsLink(Lead $lead,string $message):?string{$number=preg_replace('/[^\d+]/','',(string)$lead->phone);return $number?'sms:'.$number.'?body='.rawurlencode($message):null;}

    private function work(Request $request,Lead $lead,string $kind,array $data):void
    {abort_unless($request->user()->isAdmin()||$request->user()->allows(LeadWorkItem::permissionForKind($kind)),403);$item=$lead->workItems()->create(['kind'=>$kind,'title'=>$data['title'],'notes'=>$data['details']??null,'due_at'=>$data['due_at'],'priority'=>'Normal','status'=>'Pending','assigned_to'=>$lead->owner_id?:$request->user()->id]);$this->record($request,$lead,'System',$kind.' scheduled',$item->title.' · '.$item->due_at->format('d M Y H:i'));}

    private function opportunity(Request $request,Lead $lead,array $data):void
    {abort_unless($request->user()->allows('leads.stage'),403);$old=$lead->stage;$lead->stage=$data['stage'];if(array_key_exists('value',$data))$lead->value=$data['value'];$lead->version++;$lead->save();$this->record($request,$lead,'System','Opportunity created',$old.' → '.$lead->stage.(filled($lead->value)?' · Value '.$lead->value:'').(filled($data['details']??null)?' · '.$data['details']:''));}

    private function contact(Request $request,Lead $lead):void
    {abort_unless($request->user()->isAdmin()||$request->user()->allows('leads.edit'),403);if($lead->contact_saved_at)return;$lead->contact_saved_at=now();$lead->version++;$lead->save();$this->record($request,$lead,'System','Saved as contact');}

    private function list(Request $request,Lead $lead,int $id):void
    {abort_unless($request->user()->allows('leads.lists'),403);$group=LeadGroup::where('created_by',$request->user()->id)->findOrFail($id);if($group->leads()->syncWithoutDetaching([$lead->id])['attached'])$this->record($request,$lead,'System','Added to lead list',$group->name);}

    private function owner(Request $request,Lead $lead,int $id):void
    {abort_unless($request->user()->isAdmin()||$request->user()->allows('leads.assign'),403);$owner=User::where('active',true)->findOrFail($id);abort_unless($owner->allows('leads.view'),422);if($lead->owner_id===$owner->id)return;$old=$lead->owner?->name??'Unassigned';$lead->owner_id=$owner->id;$lead->version++;$lead->save();$this->record($request,$lead,'System','Lead assigned',$old.' → '.$owner->name);}

    private function stage(Request $request,Lead $lead,string $stage):void
    {abort_unless($request->user()->allows('leads.stage'),403);if($lead->stage===$stage)return;$old=$lead->stage;$lead->stage=$stage;if($stage==='Won'&&!$lead->contact_saved_at)$lead->contact_saved_at=now();$lead->version++;$lead->save();$this->record($request,$lead,'System','Stage changed',$old.' → '.$stage);}

    private function category(Request $request,Lead $lead,string $category):void
    {abort_unless($request->user()->allows('leads.edit'),403);$old=$lead->category?:'Not set';if($old===$category)return;$lead->category=$category;$lead->version++;$lead->save();$this->record($request,$lead,'System','Category changed',$old.' → '.$category);}

    private function delete(Request $request,Lead $lead):void
    {abort_unless($request->user()->allows('leads.delete'),403);$this->record($request,$lead,'System','Lead moved to trash','Deleted through Multi actions; recoverable from the database backup.');$lead->delete();}
}
