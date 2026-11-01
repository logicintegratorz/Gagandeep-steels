<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CrmActivity extends Model
{
    public const STATUSES = ['Complete', 'Delayed', 'Scheduled', 'ReScheduled', 'Others'];
    public const TYPES = ['General', 'Call', 'Meeting', 'Visit', 'Follow-up', 'Task', 'Opportunity'];

    protected $fillable = ['created_by', 'assigned_to', 'lead_id', 'name', 'activity_type', 'status', 'scheduled_at', 'description', 'link', 'send_calendar_invite', 'send_calendar_to_assignee', 'send_calendar_to_client', 'completion_comment', 'completed_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_at' => 'datetime', 'send_calendar_invite' => 'boolean', 'send_calendar_to_assignee' => 'boolean', 'send_calendar_to_client' => 'boolean'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function lead() { return $this->belongsTo(Lead::class); }
    public function leads() { return $this->belongsToMany(Lead::class, 'crm_activity_lead')->withTimestamps(); }
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where(fn (Builder $items) => $items->where('created_by', $user->id)->orWhere('assigned_to', $user->id));
    }
    public function getActivityCodeAttribute(): string { return 'ACT-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT); }
}
