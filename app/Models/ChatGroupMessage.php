<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ChatGroupMessage extends Model
{
    use SoftDeletes;
    protected $fillable = ['chat_group_id', 'sender_id', 'body', 'edited_at'];
    protected function casts(): array { return ['edited_at' => 'datetime']; }

    public function group() { return $this->belongsTo(ChatGroup::class, 'chat_group_id'); }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
}
