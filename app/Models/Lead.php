<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Lead extends Model {
 use SoftDeletes;
 const STAGES=['New','Initial Contact','Enquiry Confirmed','Negotiation','Site Approval','Paperwork','Won','Lost'];
 protected $guarded=['id'];
 protected static function booted():void {
  static::created(function(Lead $lead){\App\Models\LeadSourceEvent::firstOrCreate(['lead_id'=>$lead->id],['source'=>$lead->source?:'Manual','received_at'=>$lead->created_at]);});
  static::updated(function(Lead $lead){$event=\App\Models\LeadSourceEvent::where('lead_id',$lead->id)->first();if(!$event)return;$event->source=$lead->source?:'Manual';if($lead->stage==='Lost' && !$event->lost_at)$event->lost_at=now();elseif($lead->stage!=='Lost')$event->lost_at=null;$event->save();});
  static::deleting(function(Lead $lead){
   if(!$lead->isForceDeleting())\App\Models\LeadSourceEvent::where('lead_id',$lead->id)->update(['deleted_at'=>now()]);
   if(!$lead->isForceDeleting() && $lead->external_id){
    $lead->archived_external_id=$lead->external_id;
    $lead->external_id=null;
    $lead->saveQuietly();
   }
  });
  static::restored(fn(Lead $lead)=>\App\Models\LeadSourceEvent::where('lead_id',$lead->id)->update(['deleted_at'=>null]));
 }
 protected function casts(): array {return ['value'=>'decimal:2','version'=>'integer','claimed_at'=>'datetime','untouched_archived_at'=>'datetime'];}
 public function creator(){return $this->belongsTo(User::class,'created_by');}
 public function getInitialsAttribute():string {return collect(preg_split('/\s+/u',trim($this->name)))->filter()->take(2)->map(fn($word)=>mb_strtoupper(mb_substr($word,0,1)))->implode('');}
 public function owner(){return $this->belongsTo(User::class,'owner_id');}
 public function claimer(){return $this->belongsTo(User::class,'claimed_by');}
 public function repeatMatches(){return $this->hasMany(LeadRepeatMatch::class);}
 public function scopeUntouched($query){return $query->whereNull('owner_id')->whereNull('contact_saved_at')->whereDoesntHave('visits');}
 public function activities(){return $this->hasMany(LeadActivity::class);}
 public function latestTransfer(){return $this->hasOne(LeadActivity::class)->whereIn('action',['Lead assigned','Lead assigned automatically'])->latestOfMany();}
 public function latestStaffView(){return $this->hasOne(LeadActivity::class)->where('action','Lead viewed')->latestOfMany('updated_at');}
 public function visits(){return $this->hasMany(LeadVisit::class);}
 public function latestVisit(){return $this->hasOne(LeadVisit::class)->latestOfMany();}
 public function workItems(){return $this->hasMany(LeadWorkItem::class);}
 public function scopeVisibleTo($query,User $user){if(!$user->allows('leads.view_all')) $query->where(fn($q)=>$q->where('owner_id',$user->id)->orWhere(fn($pool)=>$pool->untouched()));return $query;}
}
