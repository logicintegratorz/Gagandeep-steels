<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LeadRepeatMatch extends Model {
 protected $guarded=['id'];
 public function previous(){return $this->belongsTo(Lead::class,'previous_lead_id')->withTrashed();}
}
