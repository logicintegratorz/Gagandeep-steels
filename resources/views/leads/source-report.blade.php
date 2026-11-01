<x-app-layout title="Incoming leads by source">
<link rel="stylesheet" href="{{ asset('css/dashboard.css').'?v='.filemtime(public_path('css/dashboard.css')) }}">
<div class="page-heading"><div><span class="eyebrow">LEAD REPORTING</span><h1>Incoming leads by source<span>.</span></h1><p>Review permanent received, active, lost and deleted lead counts by source and date.</p></div><a class="button secondary" href="{{ route('home') }}">← Back to dashboard</a></div>

<section class="dash-lead-source-panel" aria-labelledby="lead-source-title">
  <header><div><h2 id="lead-source-title">Source summary</h2><p>Records are retained even when their lead is deleted.</p></div><strong>{{ $totals['received'] }} total</strong></header>
  <form method="GET" action="{{ route('leads.source-report') }}" class="dash-lead-source-filters" data-source-report-filters>
    <label>Source<select name="lead_source"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(($filters['lead_source'] ?? '') === $source)>{{ $source }}</option>@endforeach</select></label>
    <label>From<input type="datetime-local" name="lead_from" value="{{ isset($filters['lead_from']) ? \Carbon\Carbon::parse($filters['lead_from'])->format('Y-m-d\TH:i') : '' }}"></label>
    <label>To<input type="datetime-local" name="lead_to" value="{{ isset($filters['lead_to']) ? \Carbon\Carbon::parse($filters['lead_to'])->format('Y-m-d\TH:i') : '' }}"></label>
    <button class="button primary">Apply</button><a class="button secondary" href="{{ route('leads.source-report') }}">Clear</a>
  </form>
  <div class="dash-lead-source-totals"><div><span>Received</span><strong>{{ $totals['received'] }}</strong></div><div><span>Active</span><strong>{{ $totals['active'] }}</strong></div><div><span>Lost</span><strong>{{ $totals['lost'] }}</strong></div><div><span>Deleted</span><strong>{{ $totals['deleted'] }}</strong></div></div>
  <div class="dash-lead-source-table"><table><thead><tr><th>Source</th><th>Total</th><th>Lost</th><th>Deleted</th><th>First received</th><th>Latest received</th><th>Latest lost</th><th>Latest deleted</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->source }}</td><td>{{ $row->total }}</td><td>{{ $row->lost }}</td><td>{{ $row->deleted }}</td><td>{{ \Carbon\Carbon::parse($row->first_received_at)->timezone('Asia/Kolkata')->format('d M Y h:i A') }}</td><td>{{ \Carbon\Carbon::parse($row->last_received_at)->timezone('Asia/Kolkata')->format('d M Y h:i A') }}</td><td>{{ $row->last_lost_at ? \Carbon\Carbon::parse($row->last_lost_at)->timezone('Asia/Kolkata')->format('d M Y h:i A') : '—' }}</td><td>{{ $row->last_deleted_at ? \Carbon\Carbon::parse($row->last_deleted_at)->timezone('Asia/Kolkata')->format('d M Y h:i A') : '—' }}</td></tr>@empty<tr><td colspan="8">No leads found for this filter.</td></tr>@endforelse</tbody></table></div>
</section>
<script>document.addEventListener('DOMContentLoaded',()=>{const form=document.querySelector('[data-source-report-filters]');if(!form)return;const from=form.elements.lead_from,to=form.elements.lead_to;from.addEventListener('change',()=>{if(!to.value)to.value=from.value})})</script>
</x-app-layout>
