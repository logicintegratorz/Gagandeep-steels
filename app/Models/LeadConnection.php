<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadConnection extends Model {
 protected $guarded=['id'];protected $hidden=['credentials'];
 protected function casts():array{return ['credentials'=>'encrypted:array','enabled'=>'boolean','last_attempt_at'=>'datetime','synced_until'=>'datetime','last_success_at'=>'datetime'];}
 public function owner(){return $this->belongsTo(User::class,'owner_id');}
}
