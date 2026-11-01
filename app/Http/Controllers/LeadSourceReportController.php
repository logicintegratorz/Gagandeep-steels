<?php

namespace App\Http\Controllers;

use App\Models\LeadSourceEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class LeadSourceReportController
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'lead_source' => ['nullable', 'string', 'max:100'],
            'lead_from' => ['nullable', 'date'],
            'lead_to' => ['nullable', 'date', 'after_or_equal:lead_from'],
        ]);

        $events = LeadSourceEvent::query();
        if (filled($filters['lead_source'] ?? null)) $events->where('source', $filters['lead_source']);
        if (filled($filters['lead_from'] ?? null)) $events->where('received_at', '>=', Carbon::parse($filters['lead_from'])->startOfMinute());
        if (filled($filters['lead_to'] ?? null)) $events->where('received_at', '<=', Carbon::parse($filters['lead_to'])->endOfMinute());

        $totals = [
            'received' => (clone $events)->count(),
            'active' => (clone $events)->whereNull('deleted_at')->whereNull('lost_at')->count(),
            'lost' => (clone $events)->whereNotNull('lost_at')->count(),
            'deleted' => (clone $events)->whereNotNull('deleted_at')->count(),
        ];

        $rows = (clone $events)
            ->selectRaw('source, COUNT(*) total, SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) deleted, SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) lost, MIN(received_at) first_received_at, MAX(received_at) last_received_at, MAX(lost_at) last_lost_at, MAX(deleted_at) last_deleted_at')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        return view('leads.source-report', [
            'filters' => $filters,
            'totals' => $totals,
            'rows' => $rows,
            'sources' => LeadSourceEvent::distinct()->orderBy('source')->pluck('source'),
        ]);
    }
}
