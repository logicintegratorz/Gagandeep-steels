<x-app-layout title="Archived untouched leads">
<link rel="stylesheet" href="{{ asset('css/leads.css').'?v='.filemtime(public_path('css/leads.css')) }}">
<script src="{{ asset('js/archived-untouched.js').'?v='.filemtime(public_path('js/archived-untouched.js')) }}" defer></script>
<nav class="lead-detail-back" aria-label="Archive navigation"><a href="{{ route('home') }}">← Back to homepage</a></nav>
<section class="panel lead-panel archived-untouched-page" data-archive-home="{{ route('home') }}">
  <header class="lead-section-header"><div><p class="lead-eyebrow">LEAD ARCHIVE</p><h2>Archived &amp; untouched leads</h2><p>Leads stay in the homepage Contacts table. This view collects leads that have not been opened or saved yet, plus earlier archived leads.</p></div><strong>{{ $leads->total() }} archived</strong></header>
  <details class="archived-add" open><summary>Archive untouched leads by date</summary><form method="POST" action="{{ route('leads.untouched.delete') }}" onsubmit="return confirm('Add untouched leads from this date range to the archive? They will remain visible on the homepage.');">@csrf<label>From<input type="date" name="from" required></label><label>To<input type="date" name="to" required></label><button class="button primary">Add to archive</button></form></details>
  <form method="GET" class="lead-filter-grid" aria-label="Filter archived untouched leads">
    @foreach(['from'=>'Archived from','to'=>'Archived to','name'=>'Lead name','company'=>'Company','phone'=>'Phone','source'=>'Source'] as $key=>$label)
      <label>{{ $label }}<input name="{{ $key }}" type="{{ in_array($key,['from','to']) ? 'date' : 'search' }}" value="{{ $filters[$key] ?? '' }}" placeholder="{{ in_array($key,['from','to']) ? '' : 'Filter '.$label }}"></label>
    @endforeach
    <div class="lead-filter-actions"><button class="button primary">Filter archive</button><a class="button secondary" href="{{ route('leads.archived') }}">Clear</a></div>
  </form>
  <div class="lead-table-wrap"><table class="lead-table"><thead><tr><th>Lead / company</th><th>Phone</th><th>Location</th><th>Source</th><th>Archived</th><th>Action</th></tr></thead><tbody>
  @forelse($leads as $lead)
    <tr><td><strong>{{ $lead->name }}</strong><small>{{ $lead->company ?: '—' }}</small></td><td>{{ $lead->phone ?: '—' }}</td><td>{{ $lead->location ?: '—' }}</td><td>{{ $lead->source ?: '—' }}</td><td>{{ $lead->untouched_archived_at?->format('d M Y H:i') }}</td><td><a class="button secondary" href="{{ route('leads.archived.show',array_merge(['leadId'=>$lead->id],$filters)) }}">View details</a></td></tr>
  @empty
    <tr><td colspan="6" class="lead-empty">No untouched or archived leads match these filters.</td></tr>
  @endforelse
  </tbody></table></div>
  @include('leads.pages',['records'=>$leads])
</section>
</x-app-layout>
