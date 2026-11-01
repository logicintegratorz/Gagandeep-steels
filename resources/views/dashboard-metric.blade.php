<x-app-layout :title="$title">
<link rel="stylesheet" href="{{ asset('css/dashboard-metric.css').'?v='.filemtime(public_path('css/dashboard-metric.css')) }}">
@if($type === 'leads')<link rel="stylesheet" href="{{ asset('css/dashboard.css').'?v='.filemtime(public_path('css/dashboard.css')) }}">@endif
<script src="{{ asset('js/dashboard-metric.js').'?v='.filemtime(public_path('js/dashboard-metric.js')) }}" defer></script>
<section class="panel lead-panel dashboard-metric-page">
    <a class="metric-back" href="{{ route('home') }}">← Back to dashboard</a>
    <header class="metric-heading"><div>@if($type !== 'calls')<span class="metric-eyebrow">BUSINESS OVERVIEW</span>@endif<h2>{{ $title }}</h2>@if($type !== 'calls')<p>Records included in this dashboard count</p>@endif</div><span class="metric-total">{{ $records->total() }} {{ $type === 'calls' ? 'call logs' : ($type === 'work' ? 'work items' : 'leads') }}</span></header>
    @if($type === 'calls')
    <form method="GET" class="metric-filters" data-auto-call-filters aria-label="Filter call details">
        <label>Company name<select name="company"><option value="">All companies</option>@foreach($companies as $company)<option value="{{ $company }}" @selected(($filters['company'] ?? '') === $company)>{{ $company }}</option>@endforeach</select></label>
        @if(auth()->user()->isAdmin())<label>User name (role)<select name="user"><option value="">All users and roles</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)($filters['user'] ?? '') === (string)$user->id)>{{ $user->name }} ({{ $user->role?->name ?? 'No role' }})</option>@endforeach</select></label>@endif
        <label>From<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" max="{{ now('Asia/Kolkata')->toDateString() }}"></label>
        <label>To<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" min="{{ $filters['from'] ?? '' }}" max="{{ now('Asia/Kolkata')->toDateString() }}"></label>
        @if(filled($filters['company'] ?? null) || filled($filters['user'] ?? null) || filled($filters['from'] ?? null) || filled($filters['to'] ?? null))<a href="{{ url()->current() }}">Clear filters</a>@endif
    </form>
    @endif
    @if($type === 'leads')
    <form method="GET" class="metric-contact-filters" aria-label="Filter leads">
      <input type="hidden" name="per_page" value="{{ $perPage }}">
      <div class="metric-table-toolbar"><strong>Lead directory</strong></div>
      <div class="dash-table-scroll metric-lead-table" tabindex="0"><table class="dash-table"><thead><tr class="dash-table-filter-row">
      @foreach(['name'=>'Contacts','company'=>'Company','stage'=>'Stage','phone'=>'Number','location'=>'Location','source'=>'Source','category'=>'Category','owner'=>'Lead Owner','added'=>'Added','score'=>'Score','followups'=>'Follow up Counts'] as $key=>$label)<th><label class="dash-table-filter"><span class="sr-only">Filter {{ $label }}</span><input name="filters[{{ $key }}]" value="{{ request('filters.'.$key) }}" type="{{ $key==='added'?'date':(in_array($key,['score','followups'])?'number':'search') }}" placeholder="Filter {{ $label }}" autocomplete="off"></label></th>@endforeach
      </tr></thead><tbody>@forelse($records as $record)<tr class="dash-contact-row"><td><a href="{{ route('leads.show',$record) }}"><strong>{{ $record->name }}</strong></a></td><td>{{ $record->company ?: '—' }}</td><td>{{ $record->stage }}</td><td>{{ $record->phone ?: '—' }}</td><td>{{ $record->location ?: '—' }}</td><td>{{ $record->source ?: '—' }}</td><td>{{ $record->category ?: '—' }}</td><td>{{ $record->owner?->name ?? 'Unassigned' }}</td><td>{{ $record->created_at->format('d M Y') }}</td><td>{{ $scoreSettings?->score($record) ?? 0 }} / 100</td><td>{{ $record->follow_up_count }}</td></tr>@empty<tr><td colspan="11" class="metric-empty">No records match this count.</td></tr>@endforelse</tbody></table></div>
      <div class="metric-filter-actions"><button class="button primary">Apply filters</button>@if(request()->filled('filters'))<a class="button secondary" href="{{ url()->current() }}">Clear filters</a>@endif</div>
    </form>
    @else
    <div class="metric-records">
    @forelse($records as $record)
        <article class="metric-record">
            @if($type === 'leads')
                <a href="{{ route('leads.show', $record) }}"><strong>{{ $record->name }}</strong></a>
                <p>{{ $record->company ?: 'No company' }} · {{ $record->stage }} · {{ $record->owner?->name ?? 'Unassigned' }}</p>
            @elseif($type === 'work')
                <a href="{{ route('leads.show', $record->lead_id) }}#lead-work"><strong>{{ $record->title }}</strong></a>
                <p>{{ $record->kind }} · {{ $record->lead?->name ?? 'Lead unavailable' }} · {{ $record->assignee?->name ?? 'Unassigned' }} · {{ $record->due_at->format('d M Y H:i') }} IST</p>
            @else
                @php($callLabel = $record->call_direction === 'Missed call' ? 'Missed call' : $record->call_direction.' call')
                @if($record->lead)<a href="{{ route('leads.show', [$record->lead, 'activity' => 'Call log', 'call_filter' => $record->call_direction === 'Missed call' ? 'missed' : strtolower((string)$record->call_direction)]) }}#lead-history"><strong>{{ $callLabel }} · {{ $record->lead->name }}</strong></a>@else<strong>{{ $callLabel }}</strong>@endif
                <p>@if(filled($record->lead?->company)){{ $record->lead->company }} · @endif{{ preg_replace('/^(?:(?:Incoming|Outgoing) call|Missed call):\s*/i', '', $record->action) }} · {{ $record->actor }}@if($record->actorUser?->role) ({{ $record->actorUser->role->name }})@endif · {{ $record->created_at->format('d M Y H:i') }}</p>
                @if($record->details)<p>{{ $record->details }}</p>@endif
            @endif
        </article>
    @empty
        <p class="metric-empty">No records match this count.</p>
    @endforelse
    </div>
    @endif
    <nav class="metric-pagination" aria-label="Record pages">
        <span>{{ $records->total() }} records · Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
        <div>
            @if($records->previousPageUrl())<a href="{{ $records->previousPageUrl() }}">← Previous</a>@endif
            @if($records->nextPageUrl())<a href="{{ $records->nextPageUrl() }}">Next →</a>@endif
        </div>
    </nav>
</section>
</x-app-layout>
