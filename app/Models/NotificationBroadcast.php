<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

final class NotificationBroadcast extends Model
{
    protected $fillable = ['sender_id','submission_key','title','message','audience','recipient_count','recalled_at'];
    protected function casts(): array { return ['recalled_at'=>'datetime']; }
    public function sender() { return $this->belongsTo(User::class,'sender_id'); }
    public function deliveries() { return $this->hasMany(CrmNotification::class,'broadcast_id'); }
}
