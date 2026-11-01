<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadList extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['active'=>'boolean'];}
 public function creator(){return $this->belongsTo(User::class,'created_by');}
 public function members(){return $this->belongsToMany(Lead::class,'lead_list_members')->withTimestamps();}
 public function automations(){return $this->hasMany(LeadListAutomation::class);}
 public function deliveries(){return $this->hasMany(LeadListDelivery::class);}
}
