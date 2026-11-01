<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeadBackupSetting extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['backup_after_days'=>'integer','delete_after_days'=>'integer','version'=>'integer'];}
}
