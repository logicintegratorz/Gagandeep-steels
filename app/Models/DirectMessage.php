<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DirectMessage extends Model
{
    use SoftDeletes;
    protected $fillable = ['sender_id', 'recipient_id', 'body', 'read_at', 'edited_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'edited_at' => 'datetime'];
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeBetweenUsers(Builder $query, int $firstUserId, int $secondUserId): Builder
    {
        return $query->where(function (Builder $messages) use ($firstUserId, $secondUserId) {
            $messages->where('sender_id', $firstUserId)->where('recipient_id', $secondUserId);
        })->orWhere(function (Builder $messages) use ($firstUserId, $secondUserId) {
            $messages->where('sender_id', $secondUserId)->where('recipient_id', $firstUserId);
        });
    }
}
