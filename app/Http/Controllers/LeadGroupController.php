<?php
namespace App\Http\Controllers;

use App\Models\{Lead,LeadGroup};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class LeadGroupController
{
    private function owned(Request $request,LeadGroup $group):void {abort_unless($group->created_by===$request->user()->id,404);}

    public function index(Request $request)
    {
        $request->validate(['lead_id'=>'nullable|integer']);
        $lead=$request->filled('lead_id')?Lead::visibleTo($request->user())->findOrFail($request->integer('lead_id')):null;
        $groups=LeadGroup::where('created_by',$request->user()->id)->withCount(['leads as visible_leads_count'=>fn($q)=>$q->visibleTo($request->user())])->orderBy('name')->get();
        $selected=$groups->firstWhere('id',$request->integer('group'))??$groups->first();
        $members=$selected?$selected->leads()->visibleTo($request->user())->with('owner')->orderBy('name')->get():collect();
        return view('leads.groups',compact('groups','selected','members','lead'));
    }

    public function store(Request $request)
    {
        $data=$request->validate(['name'=>['required','string','max:100',Rule::unique('lead_groups')->where('created_by',$request->user()->id)],'lead_id'=>'nullable|integer']);
        $lead=isset($data['lead_id'])?Lead::visibleTo($request->user())->findOrFail($data['lead_id']):null;
        $group=DB::transaction(function()use($request,$data,$lead){
            $group=LeadGroup::create(['created_by'=>$request->user()->id,'name'=>trim($data['name'])]);
            if($lead){$group->leads()->attach($lead->id);$this->record($lead,$request,'Added to lead list',$group->name);}
            return $group;
        });
        return redirect()->route('lead-groups.index',['group'=>$group->id])->with('success','List created.');
    }

    public function add(Request $request,LeadGroup $group)
    {
        $this->owned($request,$group);
        $data=$request->validate(['lead_id'=>'required|integer']);
        $lead=Lead::visibleTo($request->user())->findOrFail($data['lead_id']);
        DB::transaction(function()use($group,$lead,$request){
            if($group->leads()->syncWithoutDetaching([$lead->id])['attached'])$this->record($lead,$request,'Added to lead list',$group->name);
        });
        return back()->with('success','Lead added to '.$group->name.'.');
    }

    public function addSelected(Request $request)
    {
        $data=$request->validate(['group_id'=>'required|integer']);
        return $this->add($request,LeadGroup::findOrFail($data['group_id']));
    }

    public function remove(Request $request,LeadGroup $group,Lead $lead)
    {
        $this->owned($request,$group);
        abort_unless(Lead::visibleTo($request->user())->whereKey($lead->id)->exists(),404);
        DB::transaction(function()use($group,$lead,$request){
            if($group->leads()->detach($lead->id))$this->record($lead,$request,'Removed from lead list',$group->name);
        });
        return back()->with('success','Lead removed from list.');
    }

    private function record(Lead $lead,Request $request,string $action,string $name):void
    {
        $lead->activities()->create(['actor'=>$request->user()->name,'actor_id'=>$request->user()->id,'kind'=>'System','action'=>$action,'details'=>$name]);
    }
}
