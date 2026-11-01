<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadActivity extends Model {use \Illuminate\Database\Eloquent\SoftDeletes;protected $guarded=['id'];protected function casts():array{return ['version'=>'integer'];}public function lead(){return $this->belongsTo(Lead::class);}
 public function actorUser(){return $this->belongsTo(User::class,'actor_id');}
 public function getCallDirectionAttribute($value):?string {
  if($this->kind!=='Call log')return null;
  if(preg_match('/^(Incoming|Outgoing) call(?:$|: )/i',(string)$this->action,$matches))return ucfirst(strtolower($matches[1]));
  if(preg_match('/^Missed call(?:$|: )/i',(string)$this->action))return 'Missed call';
  if(preg_match('/^Message (sent|received)(?:$|: )/i',(string)$this->action,$matches))return 'Message '.strtolower($matches[1]);
  return in_array($value,['Incoming','Outgoing','Missed call','Message sent','Message received'],true)?$value:null;
 }
}
