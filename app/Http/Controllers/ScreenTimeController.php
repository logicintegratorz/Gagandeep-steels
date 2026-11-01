<?php

namespace App\Http\Controllers;

use App\Models\ScreenTimeEntry;
use App\Models\UserLoginEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ScreenTimeController
{
    private const HEARTBEAT_GRACE_SECONDS = 45;
    private const HEARTBEAT_MAX_SECONDS = 30;

    /** Record only the intervals during which the CRM page is focused and visible. */
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate(['state' => ['required', 'in:active,pause']]);
        $now = now('Asia/Kolkata');
        $trackedOn = $now->toDateString();

        if ($data['state'] === 'pause') {
            DB::transaction(function () use ($request, $trackedOn, $now) {
                $entry = ScreenTimeEntry::query()
                    ->where('user_id', $request->user()->id)
                    ->where('tracked_on', $trackedOn)
                    ->lockForUpdate()
                    ->first();

                if (! $entry) return;

                $elapsed = $entry->last_seen_at
                    ? $entry->last_seen_at->diffInSeconds($now, false)
                    : 0;
                $secondsToAdd = $elapsed > 0 && $elapsed <= self::HEARTBEAT_GRACE_SECONDS
                    ? (int) floor(min($elapsed, self::HEARTBEAT_MAX_SECONDS))
                    : 0;

                $entry->forceFill([
                    'seconds' => $entry->seconds + $secondsToAdd,
                    'last_seen_at' => null,
                    'last_active_at' => $now,
                ])->save();
            });

            return response()->json(['recording' => false]);
        }

        $entry = DB::transaction(function () use ($request, $trackedOn, $now) {
            $entry = ScreenTimeEntry::query()
                ->where('user_id', $request->user()->id)
                ->where('tracked_on', $trackedOn)
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                return ScreenTimeEntry::create([
                    'user_id' => $request->user()->id,
                    'tracked_on' => $trackedOn,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'last_active_at' => $now,
                ]);
            }

            $elapsed = $entry->last_seen_at
                ? $entry->last_seen_at->diffInSeconds($now, false)
                : 0;
            $secondsToAdd = $elapsed > 0 && $elapsed <= self::HEARTBEAT_GRACE_SECONDS
                ? (int) floor(min($elapsed, self::HEARTBEAT_MAX_SECONDS))
                : 0;

            $entry->forceFill([
                'seconds' => $entry->seconds + $secondsToAdd,
                'first_seen_at' => $entry->first_seen_at ?? $now,
                'last_seen_at' => $now,
                'last_active_at' => $now,
            ])->save();

            return $entry;
        });

        return response()->json(['recording' => true, 'seconds' => $entry->seconds]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $today = now('Asia/Kolkata')->toDateString();
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $filters = [
            'from' => $data['from'] ?? Carbon::parse($today)->subDays(6)->toDateString(),
            'to' => min($data['to'] ?? $today, $today),
            'user' => $data['user'] ?? null,
        ];

        $screenEntries = ScreenTimeEntry::query()
            ->with('user.role')
            ->whereBetween('tracked_on', [$filters['from'], $filters['to']])
            ->when($filters['user'], fn ($query, $user) => $query->where('user_id', $user))
            ->orderByDesc('tracked_on')
            ->orderBy('user_id')
            ->get();

        $loginEvents = UserLoginEvent::query()
            ->with('user.role')
            ->where('logged_in_at', '>=', Carbon::parse($filters['from'], 'Asia/Kolkata')->startOfDay())
            ->where('logged_in_at', '<', Carbon::parse($filters['to'], 'Asia/Kolkata')->addDay()->startOfDay())
            ->when($filters['user'], fn ($query, $user) => $query->where('user_id', $user))
            ->orderBy('logged_in_at')
            ->get();

        $rows = collect();
        foreach ($screenEntries as $entry) {
            $key = $entry->user_id.'|'.$entry->tracked_on->toDateString();
            $rows->put($key, (object) [
                'user_id' => $entry->user_id, 'user' => $entry->user, 'tracked_on' => $entry->tracked_on,
                'seconds' => $entry->seconds, 'first_seen_at' => $entry->first_seen_at,
                'last_active_at' => $entry->last_active_at, 'first_login_at' => null, 'login_count' => 0,
            ]);
        }
        foreach ($loginEvents->groupBy(fn ($event) => $event->user_id.'|'.$event->logged_in_at->timezone('Asia/Kolkata')->toDateString()) as $key => $events) {
            $first = $events->first();
            $date = $first->logged_in_at->timezone('Asia/Kolkata')->startOfDay();
            $row = $rows->get($key) ?? (object) [
                'user_id' => $first->user_id, 'user' => $first->user, 'tracked_on' => $date,
                'seconds' => 0, 'first_seen_at' => null, 'last_active_at' => null,
                'first_login_at' => null, 'login_count' => 0,
            ];
            $row->first_login_at = $events->first()->logged_in_at;
            $row->login_count = $events->count();
            $rows->put($key, $row);
        }

        $entries = $rows->sortByDesc(fn ($row) => $row->tracked_on->toDateString().'|'.$row->user_id)->values();
        $totalSeconds = $entries->sum('seconds');
        // Keep historical reporting complete even after an administrator deactivates a user.
        $users = \App\Models\User::query()->orderBy('name')->get(['id', 'name', 'active']);

        return view('screen-time.index', compact('entries', 'filters', 'totalSeconds', 'users'));
    }
}
