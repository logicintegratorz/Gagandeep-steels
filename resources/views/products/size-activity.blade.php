<section class="panel" aria-label="Size activity history">
<header class="pricing-heading"><h2>Size activity history</h2></header>
<div class="table-scroll"><table class="size-directory"><thead><tr><th>Size</th><th>Activity</th><th>Date and time (IST)</th><th>Changed by</th><th>Details</th></tr></thead><tbody>
@forelse($activities as $activity)
<tr><td>{{ $activity->size_name ?: 'Unnamed size' }}</td><td>{{ $activity->activity }}</td><td>{{ $activity->activity }} on {{ $activity->occurred_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}</td><td>{{ $activity->actor_name }}</td><td><details><summary>{{ $activity->activity === 'Deleted' ? 'Deleted values' : 'View changes' }}</summary>
@foreach(['size'=>'Size','base_price'=>'Base price','difference'=>'Basic difference','cc'=>'CC','weight'=>'Weight','notes'=>'Notes'] as $field=>$label)
@if($activity->activity === 'Deleted' || ($activity->before_values[$field] ?? null) !== ($activity->after_values[$field] ?? null))
<p><strong>{{ $label }}:</strong> {{ $activity->before_values[$field] ?? '—' }}@if($activity->activity !== 'Deleted') → {{ $activity->after_values[$field] ?? '—' }}@endif</p>
@endif
@endforeach
</details></td></tr>
@empty<tr><td colspan="5">No size edits or deletions recorded yet.</td></tr>@endforelse
</tbody></table></div></section>
