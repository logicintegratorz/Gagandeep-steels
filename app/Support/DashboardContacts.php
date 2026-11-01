<?php
namespace App\Support;

use App\Models\Lead;
use App\Models\LeadSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class DashboardContacts
{
    public static function query(Request $request): Builder
    {
        $request->validate(['filters'=>'nullable|array','filters.name'=>'nullable|string|max:100','filters.company'=>'nullable|string|max:100','filters.stage'=>'nullable|string|max:100','filters.phone'=>'nullable|string|max:60','filters.location'=>'nullable|string|max:100','filters.source'=>'nullable|string|max:100','filters.category'=>'nullable|string|max:100','filters.owner'=>'nullable|string|max:100','filters.added'=>'nullable|date_format:Y-m-d','filters.score'=>'nullable|integer|between:0,100','filters.followups'=>'nullable|integer|min:0|max:100000']);
        $contacts=Lead::visibleTo($request->user())->with('owner')->withCount(['workItems as follow_up_count'=>fn($q)=>$q->where('kind','Follow-up'),'repeatMatches'])->withExists(['visits as has_been_touched']);
        return self::applyFilters($contacts, $request);
    }

    public static function applyFilters(Builder $contacts, Request $request): Builder
    {
        foreach(['name','company','stage','phone','location','source','category'] as $field){
            if($request->filled('filters.'.$field)){
                $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],trim($request->input('filters.'.$field))).'%';
                $contacts->whereRaw("$field LIKE ? ESCAPE '!'",[$term]);
            }
        }
        if($request->filled('filters.owner')){
            $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],trim($request->input('filters.owner'))).'%';
            $contacts->whereHas('owner',fn($q)=>$q->whereRaw("name LIKE ? ESCAPE '!'",[$term]));
        }
        if($request->filled('filters.added'))$contacts->whereDate('created_at',$request->input('filters.added'));
        if($request->filled('filters.followups'))$contacts->whereRaw("(SELECT COUNT(*) FROM lead_work_items WHERE lead_work_items.lead_id = leads.id AND lead_work_items.kind = 'Follow-up') = ?",[(int)$request->input('filters.followups')]);
        if($request->filled('filters.score')){
            $weights=LeadSetting::find(1)?->weights??[];
            $pieces=[];$bindings=[];
            foreach(LeadSetting::FIELDS as $field=>$label){
                $pieces[]=$field==='created_by'?'CASE WHEN leads.created_by IS NOT NULL THEN ? ELSE 0 END':"CASE WHEN leads.$field IS NOT NULL AND leads.$field != '' THEN ? ELSE 0 END";
                $bindings[]=max(0,(int)($weights[$field]??0));
            }
            $contacts->whereRaw('LEAST(100, '.implode(' + ',$pieces).') = ?',array_merge($bindings,[(int)$request->input('filters.score')]));
        }
        return $contacts;
    }
}
