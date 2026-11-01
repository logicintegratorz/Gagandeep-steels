<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ChatGroup extends Model
{
    protected $fillable = ['name', 'created_by'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function members() { return $this->belongsToMany(User::class, 'chat_group_members')->withTimestamps(); }
    public function messages() { return $this->hasMany(ChatGroupMessage::class); }
}
