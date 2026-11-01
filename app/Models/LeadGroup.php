<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LeadGroup extends Model
{
    protected $guarded=['id'];
    public function creator(){return $this->belongsTo(User::class,'created_by');}
    public function leads(){return $this->belongsToMany(Lead::class,'lead_group_members')->withTimestamps();}
}
