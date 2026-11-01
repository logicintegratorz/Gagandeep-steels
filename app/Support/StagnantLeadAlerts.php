<?php

namespace App\Support;

use App\Models\{CrmNotification, Lead, LeadSetting, OverdueAlert, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StagnantLeadAlerts
{
    public const TYPE = 'lead_same_stage';

    public static function deliver(): int
    {
        $days = max(1, (int) LeadSetting::query()->whereKey(1)->value('same_stage_alert_days'));
        $cutoff = now()->subDays($days);
        $adminIds = User::query()->where('active', true)->whereHas('role', fn ($query) => $query->where('is_admin', true))->pluck('id');
        $delivered = 0;

        Lead::query()->with('owner:id,name,active')->whereNotIn('stage', ['Won', 'Lost'])
            ->whereNotNull('stage_changed_at')->where('stage_changed_at', '<=', $cutoff)
            ->orderBy('id')->chunkById(200, function ($leads) use ($days, $adminIds, &$delivered) {
                foreach ($leads as $lead) {
                    $ownerIds = $lead->owner?->active ? collect([$lead->owner_id]) : collect();
                    foreach ($ownerIds->merge($adminIds)->filter()->unique() as $userId) {
                        $delivered += DB::transaction(function () use ($lead, $days, $ownerIds, $userId) {
                            $marker = OverdueAlert::firstOrCreate(
                                ['source_type' => self::TYPE, 'source_id' => $lead->id, 'user_id' => $userId],
                                ['overdue_at' => $lead->stage_changed_at->copy()->addDays($days)]
                            );
                            if (!$marker->wasRecentlyCreated) return 0;
                            $adminCopy = !$ownerIds->contains($userId);
                            CrmNotification::create([
                                'user_id' => $userId,
                                'title' => Str::limit('Lead pending in '.$lead->stage.': '.$lead->name, 120, ''),
                                'message' => ($adminCopy ? 'Admin alert · Responsible: '.($lead->owner?->name ?: 'Unassigned').' · ' : '').'This lead has stayed in the same stage for '.$days.' days. Open Leads or Pipeline and update its progress.',
                                'permission' => null,
                            ]);
                            return 1;
                        });
                    }
                }
            });

        self::removeResolvedMarkers($cutoff);
        return $delivered;
    }

    private static function removeResolvedMarkers($cutoff): void
    {
        $activeIds = Lead::query()->whereNotIn('stage', ['Won', 'Lost'])->whereNotNull('stage_changed_at')->where('stage_changed_at', '<=', $cutoff)->pluck('id');
        $markers = OverdueAlert::where('source_type', self::TYPE);
        if ($activeIds->isNotEmpty()) $markers->whereNotIn('source_id', $activeIds);
        $markers->delete();
    }
}
