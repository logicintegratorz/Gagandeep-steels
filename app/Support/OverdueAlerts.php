<?php

namespace App\Support;

use App\Models\{CrmActivity, CrmNotification, LeadWorkItem, OverdueAlert, ProductivityItem, TaskStatus, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OverdueAlerts
{
    public const LEAD_WORK = 'lead_work';
    public const ACTIVITY = 'activity';
    public const PRODUCTIVITY = 'productivity';

    public static function deliver(?string $onlyType = null, ?int $onlyId = null): int
    {
        $adminIds = User::query()->where('active', true)->whereHas('role', fn ($q) => $q->where('is_admin', true))->pluck('id');
        $delivered = 0;

        if (!$onlyType || $onlyType === self::LEAD_WORK) {
            $query = LeadWorkItem::query()->with(['lead:id,name', 'assignee:id,name,active'])->whereHas('lead')
                ->where('status', 'Pending')->whereNull('completed_at')->whereNotNull('assigned_to')->where('due_at', '<', now());
            if ($onlyId) $query->whereKey($onlyId);
            $query->orderBy('id')->chunkById(200, function ($items) use ($adminIds, &$delivered) {
                foreach ($items as $item) {
                    $ownerIds = $item->assignee?->active ? collect([$item->assigned_to]) : collect();
                    $delivered += self::notify(self::LEAD_WORK, $item, $item->due_at, $item->kind, $item->title, $ownerIds, $adminIds, $item->assignee?->name, 'Lead: '.($item->lead?->name ?: 'Unavailable').'. Open Follow-ups, Tasks & Reminders.');
                }
            });
        }

        if (!$onlyType || $onlyType === self::ACTIVITY) {
            $query = CrmActivity::query()->with(['assignee:id,name,active', 'creator:id,name,active'])
                ->whereNull('completed_at')->where('status', '!=', 'Complete')->where('scheduled_at', '<', now());
            if ($onlyId) $query->whereKey($onlyId);
            $query->orderBy('id')->chunkById(200, function ($items) use ($adminIds, &$delivered) {
                foreach ($items as $item) {
                    $responsible = $item->assignee?->active ? $item->assignee : $item->creator;
                    $ownerIds = $responsible?->active ? collect([$responsible->id]) : collect();
                    $delivered += self::notify(self::ACTIVITY, $item, $item->scheduled_at, 'Activity', $item->name, $ownerIds, $adminIds, $responsible?->name, 'Open Activities.');
                }
            });
        }

        if (!$onlyType || $onlyType === self::PRODUCTIVITY) {
            $completed = TaskStatus::query()->where('is_completed', true)->pluck('name');
            $query = ProductivityItem::query()->with(['assignee:id,name,active', 'assignees:id,name,active', 'creator:id,name,active'])
                ->whereNotNull('scheduled_at')->where('scheduled_at', '<', now())->whereNotIn('status', $completed);
            if ($onlyId) $query->whereKey($onlyId);
            $query->orderBy('id')->chunkById(200, function ($items) use ($adminIds, &$delivered) {
                foreach ($items as $item) {
                    $owners = $item->assignees->where('active', true)->pluck('id');
                    if ($item->assignee?->active) $owners->push($item->assigned_to);
                    if ($owners->isEmpty() && $item->creator?->active) $owners->push($item->created_by);
                    $names = User::whereIn('id', $owners->unique())->pluck('name')->implode(', ');
                    $delivered += self::notify(self::PRODUCTIVITY, $item, $item->scheduled_at, $item->module, $item->title, $owners, $adminIds, $names, 'Open '.$item->module.'.');
                }
            });
        }

        if (!$onlyType && !$onlyId) self::removeResolvedMarkers();
        return $delivered;
    }

    private static function notify(string $type, Model $item, $dueAt, string $kind, string $title, Collection $ownerIds, Collection $adminIds, ?string $ownerNames, string $action): int
    {
        $recipientIds = $ownerIds->merge($adminIds)->filter()->unique()->values();
        $ownerIds = $ownerIds->filter()->unique();
        $count = 0;
        foreach ($recipientIds as $userId) {
            $count += DB::transaction(function () use ($type, $item, $dueAt, $kind, $title, $userId, $ownerIds, $ownerNames, $action) {
                $marker = OverdueAlert::firstOrCreate(
                    ['source_type' => $type, 'source_id' => $item->id, 'user_id' => $userId],
                    ['overdue_at' => $dueAt]
                );
                if (!$marker->wasRecentlyCreated) return 0;
                $adminCopy = !$ownerIds->contains($userId);
                CrmNotification::create([
                    'user_id' => $userId,
                    'title' => Str::limit('Overdue '.$kind.': '.$title, 120, ''),
                    'message' => ($adminCopy ? 'Admin alert · Responsible: '.($ownerNames ?: 'Unassigned').' · ' : '').'Due '.$dueAt->timezone('Asia/Kolkata')->format('d M Y, h:i A').'. '.$action,
                    'permission' => null,
                ]);
                return 1;
            });
        }
        return $count;
    }

    private static function removeResolvedMarkers(): void
    {
        $active = [
            self::LEAD_WORK => LeadWorkItem::query()->where('status', 'Pending')->whereNull('completed_at')->where('due_at', '<', now())->pluck('id'),
            self::ACTIVITY => CrmActivity::query()->whereNull('completed_at')->where('status', '!=', 'Complete')->where('scheduled_at', '<', now())->pluck('id'),
            self::PRODUCTIVITY => ProductivityItem::query()->whereNotNull('scheduled_at')->where('scheduled_at', '<', now())->whereNotIn('status', TaskStatus::where('is_completed', true)->pluck('name'))->pluck('id'),
        ];
        foreach ($active as $type => $ids) {
            OverdueAlert::where('source_type', $type)->when($ids->isNotEmpty(), fn ($q) => $q->whereNotIn('source_id', $ids), fn ($q) => $q)->delete();
        }
    }
}
