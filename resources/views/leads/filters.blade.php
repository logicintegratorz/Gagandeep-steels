<form class="lead-filters" method="GET" data-auto-lead-filters>
    @if(request('view') === 'extended')
        <input type="hidden" name="view" value="extended">
        @if(request('contacts_filter') === 'touched')
            <input type="hidden" name="contacts_filter" value="touched">
        @endif
    @endif

    <label>Search<input name="q" value="{{ request('q') }}" placeholder="Name, company, phone or email" maxlength="150"></label>
    <label>Stage<select name="stage"><option value="">All stages</option>@foreach(\App\Models\Lead::STAGES as $stage)<option @selected(request('stage') === $stage)>{{ $stage }}</option>@endforeach</select></label>
    <label>Owner<select name="owner"><option value="">All accessible owners</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected(request('owner') == $owner->id)>{{ $owner->name }}</option>@endforeach</select></label>
    <label>Job Rolls<select name="job_role"><option value="">All job rolls</option>@foreach($jobRoles as $jobRole)<option value="{{ $jobRole }}" @selected(request('job_role') === $jobRole)>{{ $jobRole }}</option>@endforeach</select></label>
    <a href="{{ request('view') === 'extended' ? route('leads.index', ['view' => 'extended', 'contacts_filter' => request('contacts_filter')]) : url()->current() }}">Clear</a>
</form>
