<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

final class CalendarAssignment extends Model {
 public $timestamps=false;
 protected $guarded=['id'];
 protected function casts():array{return ['assigned_at'=>'datetime','due_at'=>'datetime'];}
 public function actor(){return $this->belongsTo(User::class,'actor_id');}
 public function recipient(){return $this->belongsTo(User::class,'recipient_id');}
}
