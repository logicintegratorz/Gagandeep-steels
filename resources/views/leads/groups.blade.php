<x-app-layout title="Lead Lists">
    <link rel="stylesheet" href="{{ asset('css/leads.css').'?v='.filemtime(public_path('css/leads.css')) }}">
    <nav class="lead-group-tabs lead-group-page-tabs" aria-label="Your lead lists">
        @foreach($groups as $group)<a class="{{ $selected?->id===$group->id ? 'is-active' : '' }}" href="{{ route('lead-groups.index',['group'=>$group->id]) }}" @if($selected?->id===$group->id) aria-current="page" @endif>{{ $group->name }} <span>{{ $group->visible_leads_count }}</span></a>@endforeach
    </nav>
    <section class="panel lead-panel lead-groups-page">
        <header class="lead-groups-header"><div><h2>Lead Lists</h2><p>Create your own lists, then add leads from their details page. Your lists stay separate from messaging lists.</p></div></header>
        @if($lead)<p class="lead-group-context">Selected lead: <strong>{{ $lead->name }}</strong>. Give the new list a name to add this lead immediately.</p>@endif
        <form method="POST" action="{{ route('lead-groups.store') }}" class="lead-group-create">@csrf
            @if($lead)<input type="hidden" name="lead_id" value="{{ $lead->id }}">@endif
            <label>New list name<input name="name" maxlength="100" required value="{{ old('name') }}" placeholder="e.g. Follow up this week"></label>
            <button class="button primary">Create list</button>
        </form>
        @if($errors->any())<div class="lead-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if($selected)
            <div class="lead-group-tab-panel"><h3>{{ $selected->name }}</h3><p>{{ $members->count() }} {{ \Illuminate\Support\Str::plural('lead',$members->count()) }} in this list</p>
                @if($lead && !$members->contains('id',$lead->id))<form method="POST" action="{{ route('lead-groups.members.add',$selected) }}">@csrf<input type="hidden" name="lead_id" value="{{ $lead->id }}"><button class="button secondary">Add {{ $lead->name }} to this list</button></form>@endif
                <div class="lead-group-members">@forelse($members as $member)<article><div><a href="{{ route('leads.show',$member) }}">{{ $member->name }}</a><small>{{ $member->company ?: 'No company' }} · {{ $member->owner?->name ?? 'Unassigned' }}</small></div><form method="POST" action="{{ route('lead-groups.members.remove',[$selected,$member]) }}" onsubmit="return confirm('Remove this lead from the list?')">@csrf @method('DELETE')<button class="button secondary">Remove</button></form></article>@empty<p>No leads in this list yet. Open a lead and choose this list.</p>@endforelse</div>
            </div>
        @else<p class="lead-group-empty">No lists yet. Create your first list above.</p>@endif
    </section>
</x-app-layout>
