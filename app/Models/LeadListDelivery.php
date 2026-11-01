<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadListDelivery extends Model {
 protected $guarded=['id'];
 public function lead(){return $this->belongsTo(Lead::class);}
 public function list(){return $this->belongsTo(LeadList::class,'lead_list_id');}
 public function sender(){return $this->belongsTo(User::class,'sent_by');}
}
