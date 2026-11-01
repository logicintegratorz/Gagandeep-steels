<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

final class CrmNotification extends Model
{
    protected $fillable = ['user_id', 'title', 'message', 'permission', 'read_at', 'broadcast_id', 'dismissed_at'];
    protected function casts(): array { return ['read_at' => 'datetime', 'dismissed_at' => 'datetime']; }
    public function broadcast() { return $this->belongsTo(NotificationBroadcast::class); }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id)->whereNull('dismissed_at')
            ->where(fn ($q) => $q->whereNull('broadcast_id')->orWhereHas('broadcast', fn ($broadcast) => $broadcast->whereNull('recalled_at')));
        if (!$user->isAdmin()) {
            $query->where(fn ($q) => $q->whereNull('permission')->orWhereIn('permission', $user->role->permissions ?? []));
        }
    }
}
