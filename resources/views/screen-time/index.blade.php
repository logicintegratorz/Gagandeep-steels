<x-app-layout title="Screen time report">
    <link rel="stylesheet" href="{{ asset('css/screen-time.css').'?v='.filemtime(public_path('css/screen-time.css')) }}">
    @php
        $formatDuration = function (int $seconds): string {
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);
            return $hours ? $hours.'h '.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).'m' : $minutes.'m';
        };
    @endphp
    <section class="screen-time-page">
        <header class="screen-time-heading">
            <div><h1>CRM screen time report</h1><p>Shows every CRM user's focused, visible CRM time only. Switching tabs, minimizing the window, or leaving the CRM pauses the timer.</p></div>
            <a class="button secondary" href="{{ route('access.index') }}">← Users &amp; permissions</a>
        </header>

        <form class="screen-time-filters" method="GET">
            <label>From<input type="date" name="from" value="{{ $filters['from'] }}" max="{{ now('Asia/Kolkata')->toDateString() }}"></label>
            <label>To<input type="date" name="to" value="{{ $filters['to'] }}" max="{{ now('Asia/Kolkata')->toDateString() }}"></label>
            <label>User<select name="user"><option value="">All CRM users</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) $filters['user'] === (string) $user->id)>{{ $user->name }}{{ $user->active ? '' : ' (inactive)' }}</option>@endforeach</select></label>
            <button class="button" type="submit">Apply report</button>
            <a class="text-link" href="{{ route('screen-time.index') }}">Last 7 days</a>
        </form>

        <div class="screen-time-summary" aria-label="Screen time summary">
            <div><span>Total tracked time</span><strong>{{ $formatDuration($totalSeconds) }}</strong></div>
            <div><span>Users with time</span><strong>{{ $entries->pluck('user_id')->unique()->count() }}</strong></div>
            <div><span>CRM logins</span><strong>{{ $entries->sum('login_count') }}</strong></div>
            <div><span>Report period</span><strong>{{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}</strong></div>
        </div>

        <section class="panel screen-time-table-panel">
            <div class="table-scroll"><table>
                <thead><tr><th>Date</th><th>User</th><th>Role</th><th>First login</th><th>Logins</th><th>CRM screen time</th><th>First active</th><th>Last active</th></tr></thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->tracked_on->format('d M Y') }}</td>
                            <td><strong>{{ $entry->user?->name ?? 'Removed user' }}</strong></td>
                            <td>{{ $entry->user?->role?->name ?? '—' }}</td>
                            <td>{{ $entry->first_login_at?->timezone('Asia/Kolkata')->format('h:i A') ?? '—' }}</td>
                            <td>{{ $entry->login_count ?: '—' }}</td>
                            <td><strong>{{ $formatDuration($entry->seconds) }}</strong></td>
                            <td>{{ $entry->first_seen_at?->timezone('Asia/Kolkata')->format('h:i A') ?? '—' }}</td>
                            <td>{{ $entry->last_active_at?->timezone('Asia/Kolkata')->format('h:i A') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="screen-time-empty">No CRM login or focused CRM screen time was recorded for this period.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </section>
</x-app-layout>
