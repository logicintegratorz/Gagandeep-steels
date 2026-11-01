<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadBackup extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['snapshot'=>'array','backed_up_at'=>'datetime','purge_at'=>'datetime'];}
}
