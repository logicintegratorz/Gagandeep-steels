<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadListAutomation extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['active'=>'boolean','next_run_at'=>'datetime'];}
 public function list(){return $this->belongsTo(LeadList::class,'lead_list_id');}
}
