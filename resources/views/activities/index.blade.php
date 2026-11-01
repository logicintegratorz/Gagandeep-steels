<x-app-layout title="Activities">
    <link rel="stylesheet" href="{{ asset('css/activities.css').'?v='.filemtime(public_path('css/activities.css')) }}">
    <main class="activities-page" data-activities data-open-new="{{ request()->boolean('new') ? '1' : '0' }}">
        <section class="activities-card">
            <div class="activities-titlebar">
                <div><p class="eyebrow">Workspace</p><h1>Activities</h1><p class="muted">Plan, assign and complete every follow-up in one simple list.</p></div>
                <button class="button primary" type="button" data-new-activity><x-icon name="plus" size="18"/> Add new activity</button>
            </div>

            <form class="activities-filters" method="GET" aria-label="Filter activities">
                <label class="search-field"><span class="sr-only">Search activities</span><x-icon name="search" size="18"/><input name="q" value="{{ $filters['q'] }}" placeholder="Search activity"></label>
                <label><span>Status</span><select name="status" onchange="this.form.submit()"><option value="">All Status</option>@foreach(\App\Models\CrmActivity::STATUSES as $status)<option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status === 'ReScheduled' ? 'ReScheduled' : $status }}</option>@endforeach</select></label>
                <label><span>Activities</span><select name="scope" onchange="this.form.submit()"><option value="all" @selected($filters['scope'] === 'all')>All Activities</option><option value="my" @selected($filters['scope'] === 'my')>My Activities</option></select></label>
                <button class="button secondary" type="submit">Search</button>
                @if($filters['q'] || $filters['status'] || $filters['scope'] !== 'all')<a class="button ghost" href="{{ route('activities.index') }}">Clear</a>@endif
            </form>

            <div class="activity-summary"><strong>{{ $activities->total() }}</strong> activities found <span>•</span> {{ $activities->where('status', 'Complete')->count() }} complete on this page</div>
            <div class="table-scroll activity-table-wrap">
                <table class="activity-table">
                    <thead><tr><th>Activity name</th><th>Contact / opportunity</th><th>Action for</th><th>Scheduled for</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td><strong>{{ $activity->name }}</strong><small>{{ $activity->activity_code }} · {{ $activity->activity_type }}</small></td>
                            <td>@if($activity->leads->isNotEmpty())@foreach($activity->leads as $lead)<a href="{{ route('leads.show', $lead) }}">{{ $lead->name }}</a>@endforeach<small>{{ $activity->leads->count() }} linked contact{{ $activity->leads->count() === 1 ? '' : 's' }}</small>@else <span class="muted">Not linked</span>@endif</td>
                            <td>{{ $activity->assignee?->name ?? 'Unassigned' }}</td>
                            <td>{{ $activity->scheduled_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}<small>{{ $activity->created_at->diffForHumans() }}</small></td>
                            <td><span class="activity-status activity-status-{{ strtolower($activity->status) }}">{{ $activity->status === 'ReScheduled' ? 'Re-Scheduled' : $activity->status }}</span></td>
                            <td><div class="activity-actions">
                                <form method="POST" action="{{ route('activities.status', $activity) }}" data-status-form data-status-url="{{ route('activities.status', $activity) }}">@csrf<select aria-label="Update status for {{ $activity->name }}" data-status-select><option value="">Action</option><option value="Delayed">Delayed</option><option value="ReScheduled">Re-Scheduled</option><option value="Complete">Complete</option></select></form>
                                <button class="icon-button" type="button" title="Edit activity" aria-label="Edit {{ $activity->name }}" data-edit-activity data-id="{{ $activity->id }}" data-name="{{ $activity->name }}" data-type="{{ $activity->activity_type }}" data-scheduled="{{ $activity->scheduled_at->timezone('Asia/Kolkata')->format('Y-m-d\\TH:i') }}" data-assigned="{{ $activity->assigned_to }}" data-leads="{{ $activity->leads->pluck('id')->implode(',') }}" data-description="{{ $activity->description }}" data-link="{{ $activity->link }}" data-calendar-assignee="{{ $activity->send_calendar_to_assignee ? '1' : '0' }}" data-calendar-client="{{ $activity->send_calendar_to_client ? '1' : '0' }}"><x-icon name="edit" size="17"/></button>
                                <form method="POST" action="{{ route('activities.destroy', $activity) }}" data-delete-activity>@csrf @method('DELETE')<button class="icon-button danger" type="submit" title="Delete activity" aria-label="Delete {{ $activity->name }}"><x-icon name="trash" size="17"/></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="6">No activities found. Add a new activity to start tracking work.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $activities->links() }}
        </section>
    </main>

    <dialog class="activity-dialog" data-activity-dialog>
        <form method="POST" action="{{ route('activities.store') }}" data-activity-form>
            @csrf <input type="hidden" name="_method" value="POST" data-method>
            <div class="dialog-heading"><h2 data-dialog-title>Add new activity</h2><button class="icon-button" type="button" data-dialog-close aria-label="Close">×</button></div>
            <div class="activity-form-grid">
                <label class="wide">Activity name <input name="name" required maxlength="255" placeholder="e.g. Call for more details"></label>
                <label>Event date &amp; time <input type="datetime-local" name="scheduled_at" required value="{{ now('Asia/Kolkata')->addHour()->format('Y-m-d\\TH:i') }}"></label>
                <label>Activity type <select name="activity_type">@foreach(\App\Models\CrmActivity::TYPES as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></label>
                <label>Action for <select name="assigned_to"><option value="">Unassigned</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></label>
                <div class="contact-field"><span>Contact</span><div class="contact-multiselect" data-contact-picker>
                    <div class="contact-picker-control"><x-icon name="search" size="17"/><span class="contact-selection" data-contact-summary hidden></span><input type="search" autocomplete="off" placeholder="Search contacts" data-contact-search aria-label="Search contacts"><button type="button" data-contact-toggle aria-label="Show contacts" aria-expanded="false"><x-icon name="chevron" size="17"/></button></div>
                    <div class="contact-picker-menu" data-contact-menu hidden><div class="contact-options" data-contact-options>@foreach($leads as $lead)<label data-contact-option data-search-text="{{ strtolower($lead->name.' '.$lead->company) }}"><input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}"><span>{{ $lead->name }}</span><small>{{ $lead->company ?: 'Lead' }}</small></label>@endforeach</div><p class="contact-empty" data-contact-empty hidden>No contacts found.</p></div>
                </div></div>
                <label>Link <input name="link" type="url" maxlength="2048" placeholder="https://"></label>
                <div class="wide rich-description"><span>Description</span>
                    <div class="rich-editor" data-rich-editor>
                        <div class="rich-toolbar" role="toolbar" aria-label="Description formatting">
                            <button type="button" data-command="bold" title="Bold"><b>B</b></button><button type="button" data-command="italic" title="Italic"><i>I</i></button><button type="button" data-command="underline" title="Underline"><u>U</u></button><button type="button" data-command="strikeThrough" title="Strikethrough"><s>S</s></button><button type="button" data-command="formatBlock" data-value="pre" title="Code">{ }</button><button type="button" data-command="superscript" title="Superscript">X<sup>2</sup></button><button type="button" data-command="subscript" title="Subscript">X<sub>2</sub></button>
                            <select data-rich-format title="Text style"><option value="p">Normal</option><option value="h3">Heading</option><option value="pre">Code</option></select><input type="color" value="#1f3658" aria-label="Text colour" data-rich-colour>
                            <button type="button" data-command="insertUnorderedList" title="Bullet list">☷</button><button type="button" data-command="insertOrderedList" title="Numbered list">☷<sup>1</sup></button><button type="button" data-command="justifyLeft" title="Align left">≡</button><button type="button" data-command="justifyCenter" title="Align centre">≡</button><button type="button" data-command="justifyRight" title="Align right">≡</button>
                            <button type="button" data-rich-link title="Add link">⌁</button><button type="button" data-command="unlink" title="Remove link">⌁̸</button><button type="button" data-rich-emoji title="Insert emoji">☺</button><button type="button" data-rich-image title="Insert image">▧</button><button type="button" data-command="removeFormat" title="Clear formatting">⌫</button><button type="button" data-command="undo" title="Undo">↶</button><button type="button" data-command="redo" title="Redo">↷</button>
                        </div>
                        <div class="rich-editor-content" contenteditable="true" role="textbox" aria-multiline="true" data-rich-content data-placeholder="Enter description"></div>
                    </div>
                    <textarea name="description" data-rich-input hidden></textarea>
                </div>
            </div>
            <fieldset class="calendar-invite"><legend>Send Calendar invite</legend><div><label><input type="checkbox" name="send_calendar_to_assignee" value="1"> <span>To the assignee (<b data-calendar-assignee>Unassigned</b>)</span></label><label><input type="checkbox" name="send_calendar_to_client" value="1" data-calendar-client-check disabled> <span>To the client (<b data-calendar-client>Not linked</b>)</span></label></div></fieldset>
            <div class="dialog-actions"><button class="button secondary" type="button" data-dialog-close>Cancel</button><button class="button primary" type="submit" data-activity-submit>Create</button></div>
        </form>
    </dialog>
    <dialog class="activity-dialog completion-dialog" data-completion-dialog>
        <form method="POST" data-completion-form>@csrf <input type="hidden" name="status" value="Complete"><div class="dialog-heading"><div><p class="eyebrow">Complete activity</p><h2>Activity complete</h2></div><button class="icon-button" type="button" data-completion-close aria-label="Close">×</button></div><label>Comment <textarea name="completion_comment" rows="8" maxlength="10000" placeholder="Please write comment..."></textarea></label><div class="dialog-actions"><button class="button secondary" type="button" data-completion-close>Cancel</button><button class="button primary" type="submit">Save</button><button class="button primary" type="submit" name="create_new" value="1">Save &amp; create new</button></div></form>
    </dialog>
    <script src="{{ asset('js/activities.js').'?v='.filemtime(public_path('js/activities.js')) }}" defer></script>
</x-app-layout>
