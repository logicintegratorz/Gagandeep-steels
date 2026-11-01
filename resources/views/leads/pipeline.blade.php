<x-app-layout title="Pipeline">
 @include('leads.nav')
 <script src="{{ asset('js/lead-pipeline.js').'?v='.filemtime(public_path('js/lead-pipeline.js')) }}" defer></script>
 @if(auth()->user()->isAdmin())
  <link rel="stylesheet" href="{{ asset('css/lead-owner-history.css').'?v='.filemtime(public_path('css/lead-owner-history.css')) }}">
  <script src="{{ asset('js/lead-owner-history.js').'?v='.filemtime(public_path('js/lead-owner-history.js')) }}" defer></script>
 @endif
 <section class="panel lead-panel"><h2>Pipeline</h2>@include('leads.filters')
  <div class="lead-pipeline">
   @foreach($columns as $stage=>$records)
    <section class="lead-column" id="stage-{{ \Illuminate\Support\Str::slug($stage) }}" data-pipeline-stage="{{ $stage }}">
     <h3>{{ $stage }} <small>{{ $records->total() }}</small></h3>
     <div class="lead-column-body" tabindex="0" role="region" aria-label="{{ $stage }} leads">
      @forelse($records as $lead)
       <article class="lead-card {{ request('highlight')==$lead->id ? 'is-highlighted' : '' }}" draggable="{{ auth()->user()->allows('leads.stage') ? 'true' : 'false' }}" data-pipeline-card data-stage-url="{{ route('leads.stage',$lead) }}" data-version="{{ $lead->version }}" data-current-stage="{{ $lead->stage }}">
        <a href="{{ route('leads.show',$lead) }}"><strong>{{ $lead->name }}</strong></a><p>{{ $lead->company }}</p><small>@if(auth()->user()->isAdmin())<button type="button" class="lead-owner-history-trigger" data-lead-history-url="{{ route('leads.history',$lead) }}" aria-haspopup="dialog" title="View full lead history">{{ $lead->owner?->name ?? 'Unassigned' }}</button>@else{{ $lead->owner?->name ?? 'Unassigned' }}@endif · {{ $lead->priority }}</small><p>{{ $lead->value ?? '—' }}</p>
        @if(auth()->user()->allows('leads.stage'))@include('leads.stage')@endif
       </article>
      @empty<p>No leads</p>@endforelse
     </div>
     <nav class="lead-column-pages" aria-label="{{ $stage }} pages">@if($records->previousPageUrl())<a href="{{ $records->previousPageUrl() }}">Previous</a>@endif<span>{{ $records->currentPage() }} / {{ $records->lastPage() }}</span>@if($records->nextPageUrl())<a href="{{ $records->nextPageUrl() }}">Next</a>@endif</nav>
    </section>
   @endforeach
  </div>
 </section>
 @if(auth()->user()->isAdmin())<dialog class="lead-owner-history-dialog" id="lead-owner-history" aria-labelledby="lead-owner-history-title"><div data-lead-history-body></div></dialog>@endif
</x-app-layout>
