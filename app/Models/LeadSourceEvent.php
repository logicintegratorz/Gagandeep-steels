<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadSourceEvent extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['received_at'=>'datetime','lost_at'=>'datetime','deleted_at'=>'datetime'];}
}
