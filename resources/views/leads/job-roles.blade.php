<x-app-layout title="Job Rolls">
    @include('leads.nav')
    <section class="panel lead-panel">
        <h2>Job Rolls</h2>
        <p>Job rolls appear in Add Lead and All Leads filters. Editing a name updates linked leads too.</p>
        <form method="POST" action="{{ route('leads.job-roles.store') }}" class="job-role-create">
            @csrf
            <label>New job roll *<input name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Enter job roll name"></label>
            <button class="button primary" type="submit">Add job roll</button>
        </form>
        <h3>Available job rolls</h3>
        <div class="job-role-list">
            @foreach($jobRoles as $jobRole)
                @php($linkedCount = (int) ($usage[$jobRole->name] ?? 0))
                <div class="job-role-item">
                    <form method="POST" action="{{ route('leads.job-roles.update', $jobRole) }}" class="job-role-edit">
                        @csrf @method('PUT')
                        <label>Job roll name<input name="name" value="{{ $jobRole->name }}" required maxlength="100" aria-label="Edit {{ $jobRole->name }}"></label>
                        <button class="button secondary" type="submit">Save name</button>
                    </form>
                    <form method="POST" action="{{ route('leads.job-roles.destroy', $jobRole) }}" class="job-role-delete" onsubmit="return confirm('Delete this job roll?')">
                        @csrf @method('DELETE')
                        @if($linkedCount)
                            <label>{{ $linkedCount }} linked {{ \Illuminate\Support\Str::plural('lead', $linkedCount) }}. Move to:
                                <select name="replacement_id" required aria-label="Move leads from {{ $jobRole->name }} to">
                                    <option value="">Choose replacement roll</option>
                                    @foreach($jobRoles as $replacement)
                                        @if($replacement->id !== $jobRole->id)<option value="{{ $replacement->id }}">{{ $replacement->name }}</option>@endif
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <button class="button danger" type="submit" @disabled($linkedCount && $jobRoles->count() < 2)>Delete</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
