<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadWorkItem extends Model {
 public static function permissionForKind(string $kind):string{return match($kind){'Task'=>'leads.tasks','Reminder'=>'leads.reminders',default=>'leads.followups'};}
 protected $guarded=['id'];
 protected function casts(): array {return ['due_at'=>'datetime','completed_at'=>'datetime','notified_at'=>'datetime','version'=>'integer','recurring'=>'boolean','attachment_size'=>'integer'];}
 public function assignee(){return $this->belongsTo(User::class,'assigned_to');}
 public function lead(){return $this->belongsTo(Lead::class);}
 public function scopeVisibleTo($query,User $user){$query->whereHas('lead');return $user->isAdmin()?$query:$query->where('assigned_to',$user->id);}
}
