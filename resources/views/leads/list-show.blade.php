<x-app-layout :title="$list->name">
@include('leads.nav')
<div class="lead-detail-back"><a href="{{ route('leads.lists') }}">← All lists</a></div>
<section class="panel lead-panel list-page"><div class="lead-list-heading"><div><h2>{{ $list->name }}</h2><p>{{ $members->count() }} visible contacts · Created by {{ $list->creator?->name ?? 'Former user' }} · {{ $list->active ? 'Active' : 'Deactivated' }}</p></div>
<details class="list-action-menu"><summary aria-label="List settings" title="List settings">⚙ <span aria-hidden="true">⌄</span></summary><div class="list-action-popover"><strong>List actions</strong><a href="#list-contacts">View / edit members</a>@if(auth()->user()->allows('leads.broadcast') && $list->active)<button type="submit" form="list-header-email">Send email to list</button><button type="submit" form="list-header-whatsapp">Open WhatsApp chats</button>@endif @if(auth()->user()->allows('leads.automations') && $list->active)<a href="#list-automation">Schedule messages</a>@endif @if(auth()->user()->allows('leads.lists'))<a href="#list-editor" data-open-list-editor>Rename list</a><button type="submit" form="list-header-active">{{ $list->active ? 'Deactivate list' : 'Activate list' }}</button><button class="is-danger" type="submit" form="list-header-delete">Remove list</button>@endif</div></details>
</div>
@if(auth()->user()->allows('leads.broadcast') && $list->active)<form id="list-header-email" method="POST" action="{{ route('leads.lists.send-all',$list) }}" onsubmit="return confirm('Send this list message by email to every visible contact?')">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="channel" value="Email"></form><form id="list-header-whatsapp" method="POST" action="{{ route('leads.lists.send-all',$list) }}">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="channel" value="WhatsApp"></form>@endif
@if(auth()->user()->allows('leads.lists'))<form id="list-header-active" method="POST" action="{{ route('leads.lists.active',$list) }}">@csrf</form><form id="list-header-delete" method="POST" action="{{ route('leads.lists.destroy',$list) }}" onsubmit="return confirm('Remove this list and its schedules? Contacts remain in CRM.')">@csrf @method('DELETE')</form>@endif

@if(auth()->user()->allows('leads.lists'))<details class="list-editor" id="list-editor" @if(request('edit')) open @endif><summary>Edit name and message</summary><form method="POST" action="{{ route('leads.lists.update',$list) }}">@csrf @method('PUT')<label>Name<input name="name" value="{{ $list->name }}" maxlength="150" required></label><label>Message / description<textarea name="description" maxlength="500" rows="4" required>{{ $list->description }}</textarea></label><button class="button primary">Save changes</button></form></details>
@endif

<div class="list-message"><small>Message</small><p>{{ $list->description }}</p><small>Email subject: {{ $list->name }}. For WhatsApp, CRM opens the lead’s chat with this message prefilled; you press Send there.</small></div>
@if($list->deliveries->contains(fn($delivery) => $delivery->channel === 'Email' && \App\Support\LeadEmailFailure::authenticationRejected($delivery->error)))
<div class="lead-stage-notice" role="alert"><strong>Email sending needs attention.</strong> The mail server rejected the saved SMTP login. No email was sent for those failed attempts. @if(auth()->user()->isAdmin())<a href="{{ route('leads.settings') }}">Update email connection</a>@else Ask your administrator to update the email connection.@endif After a successful test send, resume any paused schedules below.</div>
@endif
@if(auth()->user()->isAdmin() && (!$settings->emailReady() || !$settings->whatsappReady()))
<div class="list-setup-note"><strong>Connect a sending channel</strong><span>
@if(!$settings->emailReady())<a href="{{ route('leads.settings') }}">Set up email</a>@endif
@if(!$settings->whatsappReady())<a href="{{ route('leads.lists.whatsapp-settings') }}">Set up WhatsApp</a>@endif
</span></div>
@endif

@if(auth()->user()->allows('leads.lists'))<section class="list-add"><h3>Add contacts</h3><p>Search by name, company or email. Select any accessible leads to include in this list.</p><form method="GET" class="list-search"><input name="q" value="{{ request('q') }}" placeholder="Search contacts to add"><button class="button secondary">Search</button> @if(request('q'))<a class="button secondary" href="{{ route('leads.lists.show',$list) }}">Clear</a> @endif</form>
@if($available->isNotEmpty())<form method="POST" action="{{ route('leads.lists.members.add',$list) }}">@csrf<div class="list-picker">
@foreach($available as $lead)<label><input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}"><span><strong>{{ $lead->name }}</strong><small>{{ $lead->company ?: 'No company' }} · {{ $lead->email ?: ($lead->whatsapp ?: $lead->phone ?: 'No contact method') }}</small></span></label>
@endforeach
</div><button class="button primary">Add selected contacts</button></form>
@else
<p>No contacts match this search, or they are already in the list.</p>
@endif
</section>
@endif

</section>
<section class="panel lead-panel list-page" id="list-contacts"><div class="lead-list-heading"><div><h2>List contacts</h2><p>Select one or several contacts, or choose all to send the list message.</p></div><span class="lead-list-count">{{ $members->count() }} contacts</span></div>
@if(!$list->active)<p class="lead-stage-notice">This list is deactivated. Activate it from List settings to send messages or create schedules.</p>@endif

@if($members->isNotEmpty())<form method="POST" action="{{ route('leads.lists.send',$list) }}" id="list-send-form">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
@if(auth()->user()->allows('leads.broadcast') && $list->active)<div class="list-send-bar"><label><input type="checkbox" id="list-select-all"> Select all</label><span><output id="list-selected-count">0</output> selected</span><label>Send via<select name="channel" id="list-channel"><option value="Email">Email {{ $settings->emailReady() ? '' : '(setup needed)' }}</option><option value="WhatsApp">WhatsApp (open chats)</option></select></label><button class="button primary" id="list-send-button" disabled>Continue</button></div>
@endif
 <div class="table-scroll"><table class="lead-directory"><thead><tr><th></th><th>Contact</th><th>Company</th><th>Email</th><th>WhatsApp / phone</th><th>Action</th></tr></thead><tbody>
@foreach($members as $lead)<tr><td>
@if(auth()->user()->allows('leads.broadcast') && $list->active)<input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" data-list-select aria-label="Select {{ $lead->name }}">
@endif
</td><td><a href="{{ route('leads.show',$lead) }}">{{ $lead->name }}</a></td><td>{{ $lead->company ?: '—' }}</td><td>{{ $lead->email ?: '—' }}</td><td>{{ $lead->whatsapp ?: ($lead->phone ?: '—') }}</td><td>
<details class="list-action-menu"><summary aria-label="Settings for {{ $lead->name }}" title="Contact settings">⚙ <span aria-hidden="true">⌄</span></summary><div class="list-action-popover"><strong>{{ $lead->name }}</strong><a href="{{ route('leads.show',$lead) }}">View lead details</a>@if(auth()->user()->allows('leads.edit'))<a href="{{ route('leads.edit',$lead) }}">Edit contact</a>@endif @if(auth()->user()->allows('leads.broadcast') && $list->active)<button type="submit" form="email-{{ $lead->id }}">Send email</button><button type="submit" form="whatsapp-{{ $lead->id }}">Open WhatsApp chat</button>@endif @if(auth()->user()->allows('leads.automations') && $list->active)<a href="#list-automation">Schedule messages</a>@endif @if(auth()->user()->allows('leads.lists'))<button class="is-danger" type="submit" form="remove-{{ $lead->id }}">Remove from list</button>@endif</div></details>
</td></tr>
@endforeach
</tbody></table></div></form>
@if(auth()->user()->allows('leads.broadcast') && $list->active)
<form method="POST" action="{{ route('leads.lists.send-all',$list) }}" class="list-send-all">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><label>Entire list via<select name="channel"><option value="Email">Email</option><option value="WhatsApp">WhatsApp (open chats)</option></select></label><button class="button secondary">Continue with all {{ $members->count() }} contacts</button><small>WhatsApp opens one chat per contact for you to send manually.</small></form>
@endif
@foreach($members as $lead)
@if(auth()->user()->allows('leads.lists'))<form id="remove-{{ $lead->id }}" method="POST" action="{{ route('leads.lists.members.remove',[$list,$lead]) }}" onsubmit="return confirm('Remove this contact from the list? The lead stays in CRM.')">@csrf @method('DELETE')</form>
@endif
@if(auth()->user()->allows('leads.broadcast') && $list->active)<form id="email-{{ $lead->id }}" method="POST" action="{{ route('leads.lists.send',$list) }}" onsubmit="return confirm('Send the list message by email to this contact?')">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="channel" value="Email"><input type="hidden" name="lead_ids[]" value="{{ $lead->id }}"></form><form id="whatsapp-{{ $lead->id }}" method="POST" action="{{ route('leads.lists.send',$list) }}">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="channel" value="WhatsApp"><input type="hidden" name="lead_ids[]" value="{{ $lead->id }}"></form>@endif
 
@endforeach


@else
<p class="lead-empty-state">Add contacts above to start sending.</p>
@endif
</section>

@if(auth()->user()->allows('leads.automations'))<section class="panel lead-panel list-page" id="list-automation"><div class="lead-list-heading"><div><h2>Automatic messages</h2><p>Choose where to send this list’s message and when. CRM sends it to current list contacts at the scheduled time.</p></div></div><div class="automation-channel-status"><span>Email: <strong>{{ $settings->emailReady() ? 'Ready' : 'Setup needed' }}</strong></span><span>WhatsApp: <strong>{{ $settings->whatsappReady() ? 'Ready' : 'Setup needed' }}</strong></span>@if(auth()->user()->isAdmin())<a href="{{ route('leads.settings') }}">Email settings</a><a href="{{ route('leads.lists.whatsapp-settings') }}">WhatsApp settings</a>@endif</div>@if($list->active)<form method="POST" action="{{ route('leads.lists.automations.store',$list) }}" class="list-schedule">@csrf<label>Send by<select name="channel" required><option value="Email" @selected(old('channel')==='Email')>Email {{ $settings->emailReady() ? '✓' : '— setup needed' }}</option><option value="WhatsApp" @selected(old('channel')==='WhatsApp')>WhatsApp {{ $settings->whatsappReady() ? '✓' : '— setup needed' }}</option></select></label><label>Repeat<select name="frequency" id="schedule-frequency">@foreach(['Once','Daily','Weekly','Monthly'] as $frequency)<option value="{{ $frequency }}" @selected(old('frequency')===$frequency)>{{ $frequency }}</option>@endforeach</select></label><label>Send at (IST)<input name="send_time" type="time" required value="{{ old('send_time','09:00') }}"></label><label id="schedule-week" hidden>Weekday<select name="day_of_week">
@foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i=>$day)<option value="{{ $i }}">{{ $day }}</option>
@endforeach
</select></label><label id="schedule-month" hidden>Day of month<input name="day_of_month" type="number" min="1" max="28" value="1"></label><button class="button primary">Schedule messages</button></form><p class="lead-section-help">The list message above is sent automatically. For WhatsApp, use an approved Meta template with one message variable. Add contacts before the scheduled time. You can pause a schedule below.</p>@else<p class="lead-section-help">Activate this list to create or resume schedules.</p>@endif
<div class="list-automation-cards">
@forelse($list->automations as $automation)<article class="automation-card"><div><strong>{{ $automation->channel }} · {{ $automation->frequency }}</strong><small>{{ $automation->active ? (\App\Support\LeadListMessenger::ready($automation->channel,$settings) ? 'Active' : 'Connection needed') : 'Paused — will not send until resumed' }} · {{ $automation->send_time }} IST 
@if($automation->active && $automation->next_run_at) · Next {{ $automation->next_run_at->format('d M Y H:i') }}
@endif
</small></div><form method="POST" action="{{ route('leads.lists.automations.toggle',[$list,$automation]) }}">@csrf<button class="button secondary">{{ $automation->active ? 'Pause' : 'Resume' }}</button></form></article>@empty<p>No scheduled messages yet.</p>
@endforelse
</div></section>
@endif

<section class="panel lead-panel list-page"><h2>Recent sends</h2><div class="list-automation-cards">
@forelse($list->deliveries as $delivery)<article><div><strong>{{ $delivery->lead?->name ?? $delivery->recipient }} · {{ $delivery->channel }}</strong><small>{{ $delivery->created_at->format('d M Y H:i') }} IST · {{ $delivery->status }}</small>
@if(in_array($delivery->status,['Unconfirmed','Failed']))<small>{{ $delivery->channel === 'Email' ? \App\Support\LeadEmailFailure::message($delivery->error) : ($delivery->error ?: 'Provider acceptance could not be confirmed. Check before retrying.') }}</small>
@endif
</div></article>@empty<p>No messages sent yet.</p>
@endforelse
</div><p class="lead-section-help">Accepted means the email server or WhatsApp accepted the request. Final delivery depends on the provider.</p></section>

@if(session('send_issues'))<div class="lead-errors" role="alert">
@foreach(session('send_issues') as $issue)<p>{{ $issue }}</p>
@endforeach
</div>
@endif

<script src="{{ asset('js/lead-lists.js').'?v='.filemtime(public_path('js/lead-lists.js')) }}" defer></script>
</x-app-layout>
