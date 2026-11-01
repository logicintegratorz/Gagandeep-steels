<x-app-layout :title="$lead->name">
<link rel="stylesheet" href="{{ asset('css/leads.css').'?v='.filemtime(public_path('css/leads.css')) }}">
<script src="{{ asset('js/archived-untouched.js').'?v='.filemtime(public_path('js/archived-untouched.js')) }}" defer></script>
<nav class="lead-detail-back" aria-label="Archive navigation"><a href="{{ $returnTo }}">← Back to archived untouched leads</a></nav>
<section class="panel lead-panel archived-untouched-page" data-archive-home="{{ route('home') }}">
  <header class="lead-section-header"><div><p class="lead-eyebrow">ARCHIVED LEAD</p><h2>{{ $lead->name }}</h2><p>{{ $lead->company ?: 'No company added' }} · {{ $lead->untouched_archived_at ? 'Archived '.$lead->untouched_archived_at->format('d M Y H:i') : 'Untouched lead' }}</p></div><span class="dash-untouched-badge">Untouched / archived lead</span></header>
  <div class="lead-details-grid">
    @foreach(['Phone'=>$lead->phone,'Alt number'=>$lead->alt_phone,'Email'=>$lead->email,'Location'=>$lead->location,'Source'=>$lead->source,'Category'=>$lead->category,'Job role'=>$lead->job_role,'Stage'=>$lead->stage] as $label=>$value)<div><small>{{ $label }}</small><strong>{{ $value ?: 'Not added' }}</strong></div>@endforeach
  </div>
  @if($lead->notes)<section class="archived-notes"><h3>Inquiry notes</h3><p class="lead-pre">{{ $lead->notes }}</p></section>@endif
  <section class="archived-notes"><h3>Archived activity</h3><div class="lead-activity-feed">@forelse($lead->activities as $activity)<article class="lead-timeline-row"><span class="activity-initial">A</span><div><strong>{{ $activity->action }}</strong><small>{{ $activity->actor }} · {{ $activity->created_at->format('d M Y H:i') }}</small><p class="lead-pre">{{ $activity->details }}</p></div></article>@empty<p>No archived activity recorded.</p>@endforelse</div></section>
</section>
</x-app-layout>
