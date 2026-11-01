<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

final class CrmAuditEvent extends Model {
 public $timestamps=false;
 protected $guarded=['id'];
 protected function casts():array{return ['occurred_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
}
