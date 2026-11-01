<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ProductivityItem extends Model
{
    public const MODULES = ['Reminder', 'Meeting', 'To-Do', 'Note'];
    protected $fillable = ['module','status','created_by','assigned_to','title','message','scheduled_at','recurrence','priority','carry_forward','send_instant','template_type','template_name','email_automation','whatsapp_automation','customer_email','customer_mobile','attachment_name','attachment_path','attachment_mime','attachment_size'];
    protected function casts(): array { return ['scheduled_at'=>'datetime','carry_forward'=>'boolean','send_instant'=>'boolean','email_automation'=>'boolean','whatsapp_automation'=>'boolean']; }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function assignees() { return $this->belongsToMany(User::class, 'productivity_item_user'); }
    public function scopeVisibleTo(Builder $query, User $user): Builder { return $user->isAdmin() ? $query : $query->where(fn($q)=>$q->where('created_by',$user->id)->orWhere('assigned_to',$user->id)->orWhereHas('assignees', fn($users)=>$users->whereKey($user->id))); }
}
