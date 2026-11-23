<x-app-layout title="Contacts">
    <link rel="stylesheet" href="{{ asset('css/leads.css').'?v='.filemtime(public_path('css/leads.css')) }}">
    <script src="{{ asset('js/contact-search.js').'?v='.filemtime(public_path('js/contact-search.js')) }}" defer></script>

    <section class="panel lead-panel">
        <header class="lead-list-heading">
            <div>
                <h2>Contacts</h2>
                <p>{{ $canViewAll ? 'Contacts saved through the Add Contact form are shown here.' : 'Only contacts you created or own are shown here.' }}</p>
            </div>
            @if(auth()->user()->allows('leads.create'))
                <a class="button primary contact-add-button" href="{{ route('leads.create') }}" aria-label="Add contact" title="Add contact"><x-icon name="plus" size="18"/></a>
            @endif
        </header>

        <nav class="contact-role-tabs" aria-label="Filter contacts by job role">
            <a class="contact-role-tab {{ request('job_role') ? '' : 'is-active' }}" href="{{ route('contacts.index', array_filter(['q' => request('q')])) }}">All contacts <span>{{ $contactCount }}</span></a>
            @foreach($jobRoles as $jobRole)
                <a class="contact-role-tab {{ request('job_role') === $jobRole ? 'is-active' : '' }}" href="{{ route('contacts.index', array_filter(['q' => request('q'), 'job_role' => $jobRole])) }}">{{ $jobRole }} <span>{{ $roleCounts[$jobRole] ?? 0 }}</span></a>
            @endforeach
        </nav>

        <form class="lead-filters" method="GET" data-contact-search-form>
            @if(request('job_role'))<input type="hidden" name="job_role" value="{{ request('job_role') }}">@endif
            <label>Search contacts<input name="q" value="{{ request('q') }}" maxlength="150" placeholder="Name, company, number or email" autocomplete="off" data-contact-search-input></label>
            <a href="{{ route('contacts.index') }}">Clear</a>
        </form>

        <div class="table-scroll" tabindex="0" role="region" aria-label="Contacts saved through the Add Contact form">
            <table class="lead-directory">
                <thead><tr><th>Name</th><th>Company</th><th>Numbers</th><th>Email</th><th>Location</th><th>Owner</th><th>Created on</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($contacts as $contact)
                    <tr>
                        <td><a class="text-link" href="{{ route('leads.show', ['lead' => $contact, 'from' => 'contacts']) }}"><strong>{{ $contact->name }}</strong></a><small>{{ $contact->stage }}</small></td>
                        <td>{{ $contact->company ?: '—' }}</td>
                        <td>{{ $contact->phone ?: '—' }}@if($contact->alt_phone)<small>Alt: {{ $contact->alt_phone }}</small>@endif @if($contact->whatsapp)<small>WhatsApp: {{ $contact->whatsapp }}</small>@endif</td>
                        <td>{{ $contact->email ?: '—' }}</td>
                        <td>{{ $contact->location ?: '—' }}</td>
                        <td>{{ $contact->owner?->name ?? 'Unassigned' }}</td>
                        <td>{{ ($contact->contact_saved_at ?? $contact->created_at)?->format('d M Y, h:i A') }}</td>
                        <td class="contact-actions">@if(auth()->user()->allows('leads.edit'))<a class="contact-action" href="{{ route('leads.edit', $contact) }}" aria-label="Edit {{ $contact->name }}" title="Edit contact"><x-icon name="edit" size="17"/></a>@endif @if(auth()->user()->allows('leads.delete'))<form method="POST" action="{{ route('leads.destroy', $contact) }}" class="inline-form" onsubmit="return confirm('Delete this contact?')">@csrf @method('DELETE')<input type="hidden" name="version" value="{{ $contact->version }}"><button type="submit" class="contact-action contact-delete" aria-label="Delete {{ $contact->name }}" title="Delete contact"><x-icon name="trash" size="17"/></button></form>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="lead-muted">No contacts saved through the Add Contact form yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @include('leads.pages', ['records' => $contacts])
    </section>
</x-app-layout>
