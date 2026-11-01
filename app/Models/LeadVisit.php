<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LeadVisit extends Model {
 protected $guarded=['id'];
 protected function casts():array {return ['opened_at'=>'datetime','last_seen_at'=>'datetime','closed_at'=>'datetime','stage_updated_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
 public function lead(){return $this->belongsTo(Lead::class);}
 public function status():string {
  if($this->stage_updated_at)return 'Stage updated';
  if($this->closed_at)return 'Left without update';
  return $this->last_seen_at?->greaterThan(now()->subSeconds(90))?'Opened now':'No recent activity';
 }
}
