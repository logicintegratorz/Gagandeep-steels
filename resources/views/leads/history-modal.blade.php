<div class="lead-owner-history-header"><div><h2 id="lead-owner-history-title">{{ $lead->name }} · full history</h2><p>{{ $lead->trashed() ? 'Archived lead' : 'Current owner: '.($lead->owner?->name ?? 'Unassigned') }} · From first record to now</p></div><button type="button" class="button secondary" data-lead-history-close aria-label="Close history">Close</button></div>
<div class="lead-owner-history-summary"><strong>Recorded details</strong><p>{{ collect([$lead->company,$lead->phone,$lead->location,$lead->source])->filter()->implode(' · ') }}</p>@if($lead->archived_external_id ?? $lead->external_id)<p>Inquiry ID: {{ $lead->archived_external_id ?? $lead->external_id }}</p>@endif @if($lead->notes)<p class="lead-owner-history-notes">{{ $lead->notes }}</p>@endif</div>
<label class="lead-owner-history-filter">Show activity by user
 <select data-lead-history-user aria-label="Filter lead history by user"><option value="">All users</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($selected===$user->id)>{{ $user->name }}</option>@endforeach</select>
</label>
<p class="lead-owner-history-count">{{ $events->count() }} {{ \Illuminate\Support\Str::plural('event',$events->count()) }}</p>
<div class="lead-owner-history-list">@forelse($events as $event)
 <article class="lead-owner-history-event"><time datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</time><div><strong>{{ $event['title'] }}</strong><small>By {{ $event['actor'] }}</small>@if($event['details'])<p>{{ $event['details'] }}</p>@endif</div></article>
 @empty<p>No recorded activity for this user on this lead.</p>@endforelse</div>
