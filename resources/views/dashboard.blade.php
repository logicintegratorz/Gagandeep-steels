<!doctype html>
<html lang="en"><head><script>try{const saved=localStorage.getItem('crm-theme');const theme=saved==='dark'?'dark':'light';document.documentElement.dataset.theme=theme;document.documentElement.dataset.appearance=theme}catch(e){document.documentElement.dataset.theme='light';document.documentElement.dataset.appearance='light'}</script>

    <meta charset="utf-8"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard · Gagandeep Steels</title>
    <link rel="icon" href="{{ asset('images/gagandeep-steels-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/feedback.css').'?v='.filemtime(public_path('css/feedback.css')) }}">
    @if(auth()->user()->isAdmin())<link rel="stylesheet" href="{{ asset('css/lead-owner-history.css').'?v='.filemtime(public_path('css/lead-owner-history.css')) }}"><script src="{{ asset('js/lead-owner-history.js').'?v='.filemtime(public_path('js/lead-owner-history.js')) }}" defer></script>@endif
    <link rel="stylesheet" href="{{ asset('css/dashboard.css').'?v='.filemtime(public_path('css/dashboard.css')) }}">
    <script src="{{ asset('js/dashboard-call.js').'?v='.filemtime(public_path('js/dashboard-call.js')) }}" defer></script>
    <script src="{{ asset('js/dashboard-messages.js').'?v='.filemtime(public_path('js/dashboard-messages.js')) }}" defer></script>
    <script src="{{ asset('js/dashboard-quick-call.js').'?v='.filemtime(public_path('js/dashboard-quick-call.js')) }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/calculator.css').'?v='.filemtime(public_path('css/calculator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css').'?v='.filemtime(public_path('css/theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/calendar.css').'?v='.filemtime(public_path('css/calendar.css')) }}">
    <script src="{{ asset('js/theme.js').'?v='.filemtime(public_path('js/theme.js')) }}" defer></script>
    <script src="{{ asset('js/calendar.js').'?v='.filemtime(public_path('js/calendar.js')) }}" defer></script>
    <script src="{{ asset('js/dashboard-filters.js').'?v='.filemtime(public_path('js/dashboard-filters.js')) }}" defer></script>
    <script src="{{ asset('js/calculator.js').'?v='.filemtime(public_path('js/calculator.js')) }}" defer></script>
    <script src="{{ asset('js/app.js').'?v='.filemtime(public_path('js/app.js')) }}" defer></script>
    <script src="{{ asset('js/ui.js').'?v='.filemtime(public_path('js/ui.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/compact.css').'?v='.filemtime(public_path('css/compact.css')) }}"></head>
<body class="dashboard-page">
<a class="skip-link" href="#main">Skip to content</a>
<x-sidebar/>
<x-screen-time/>
<div class="main">
<div class="dash-top-stack">
<header class="dash-header">
    <a class="dash-brand" href="{{ route('home') }}"><img src="{{ asset('images/gagandeep-steels-logo.png') }}" alt="Gagandeep Steels — Iron & Steel Suppliers" width="267" height="81"></a>
    <nav class="dash-shortcuts" aria-label="Quick actions">
        <button type="button" class="dash-tool-button calculator-trigger" data-calculator-open aria-label="Open calculator" title="Calculator"><x-icon name="calculator" size="20"/></button>
        <button type="button" class="dash-tool-button theme-toggle" data-theme-toggle aria-label="Use dark theme" title="Switch theme"><span data-theme-icon="dark"><x-icon name="moon" size="20"/></span><span data-theme-icon="light" hidden><x-icon name="sun" size="20"/></span></button>
        @if(auth()->user()->allows('products.view'))<a href="{{ route('products.pricing') }}"><x-icon name="rupee"/><span>Rates</span></a>@endif
        @if(auth()->user()->isAdmin())
            <button type="button" disabled title="Quotation workspace is coming soon"><x-icon name="document"/><span>Quotation</span></button>
            <button type="button" disabled title="SMS workspace is coming soon"><x-icon name="chat"/><span>SMS</span></button>
            <button type="button" disabled title="WhatsApp workspace is coming soon"><x-icon name="chat"/><span>WhatsApp</span></button>
            <button type="button" disabled title="Email workspace is coming soon"><x-icon name="mail"/><span>Email</span></button>
            <button type="button" disabled title="Call workspace is coming soon"><x-icon name="phone"/><span>Call</span></button>
        @endif
    </nav>
    
@php
    $dashAccountParts = preg_split('/\s+/', trim((string) auth()->user()->name), -1, PREG_SPLIT_NO_EMPTY);
    $dashAccountInitials = mb_strtoupper(
        mb_substr($dashAccountParts[0] ?? 'U', 0, 1)
        . (count($dashAccountParts) > 1 ? mb_substr($dashAccountParts[array_key_last($dashAccountParts)], 0, 1) : '')
    );
@endphp
<div class="dash-account">
    <x-notification-bell/>

    <details data-dashboard-account-control data-dashboard-account-ready>
        <summary aria-label="Account: {{ auth()->user()->name }}">
            <span class="dash-avatar">@if(auth()->user()->avatar_path)<img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="">@else{{ $dashAccountInitials }}@endif</span>
        </summary>

        <div class="dash-account-menu">
            <strong>{{ auth()->user()->name }}</strong>
            <a href="{{ route('account.index') }}">My account</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </div>
    </details>
</div>

</header>
@php($announcements = \App\Models\CrmNotification::visibleTo(auth()->user())->whereNotNull('broadcast_id')->whereDate('created_at', now('Asia/Kolkata')->toDateString())->with('broadcast')->orderByDesc('id')->limit(10)->get())
@php($dashboardMessages = \App\Models\CrmNotification::visibleTo(auth()->user())->orderByDesc('id')->limit(30)->get())
<div class="dash-announcement"><button class="icon-button" id="menu-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="sidebar"><x-icon name="menu"/></button><span class="announcement-label"><x-icon name="bell" size="15"/> {{ $announcements->first()?->title ?? 'Hi, '.auth()->user()->name }}</span>
<div class="announcement-ticker" tabindex="0" aria-label="Admin messages">
@if($announcements->isNotEmpty())
<div class="announcement-track" style="--ticker-duration: {{ max(25, $announcements->sum(fn($notice) => mb_strlen($notice->title.' '.$notice->broadcast->message)) / 7) }}s">
@foreach($announcements as $notice)<span class="announcement-message"><strong>{{ $notice->title }}</strong> — {{ $notice->broadcast->message }}</span>@endforeach
</div>
@else<span>Welcome to Gagandeep Steels. Your workday, all in one place.</span>@endif
</div><button type="button" class="dash-view-messages" data-dashboard-messages-open aria-haspopup="dialog">View messages <span aria-hidden="true">↗</span></button></div>
<dialog class="dashboard-messages-dialog" data-dashboard-messages aria-labelledby="dashboard-messages-title">
  <section class="dashboard-messages-card">
    <header><div><p class="dashboard-messages-eyebrow">CRM INBOX</p><h2 id="dashboard-messages-title">Messages</h2><p>{{ $dashboardMessages->whereNull('read_at')->count() }} unread · latest 30 records</p></div><button type="button" class="icon-button" data-dashboard-messages-close aria-label="Close messages">×</button></header>
    <div class="dashboard-messages-actions"><form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="button secondary" @disabled($dashboardMessages->whereNull('read_at')->isEmpty())>Mark all as read</button></form></div>
    <div class="dashboard-messages-list">
      @forelse($dashboardMessages as $message)
        <article class="dashboard-message {{ $message->read_at ? 'is-read' : 'is-unread' }}">
          <div class="dashboard-message-copy"><strong>{{ $message->title }}</strong><p>{{ $message->message }}</p><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</time></div>
          <div class="dashboard-message-controls">@unless($message->read_at)<form method="POST" action="{{ route('notifications.read', $message->id) }}">@csrf @method('PATCH')<button type="submit" class="button secondary">Mark read</button></form>@endunless<form method="POST" action="{{ route('notifications.destroy', $message->id) }}">@csrf @method('DELETE')<button type="submit" class="button secondary">Dismiss</button></form></div>
        </article>
      @empty
        <div class="dashboard-messages-empty"><strong>No messages yet</strong><p>New alerts, assignments and admin messages will appear here.</p></div>
      @endforelse
    </div>
  </section>
</dialog>
</div>
<main id="main" class="dash-main">
    <div class="dash-heading"><div></div><div class="dash-overview-actions"><span class="dash-date">{{ now('Asia/Kolkata')->format('l, d M Y') }}</span><button type="button" class="dash-chart-toggle" data-dashboard-chart-toggle aria-pressed="false" aria-label="Show pie chart view" title="Show pie chart view"><x-icon name="chart" size="17"/></button></div></div>
    <?php
    $groups = $dashboardGroups;
    if (auth()->user()->isAdmin()) {
      $groups['Call Details'] = $callCounts + ['Missed Calls' => $missedCallsCount] + array_fill_keys(['SMS sent','WhatsApp sent'], null);
    }
    $viewAllUrls = [
      'Total Leads' => route('leads.index'),
      'Total Tasks' => route('leads.work', ['kind' => 'Task']),
      'Opportunities' => route('leads.pipeline'),
      'Reminders' => route('leads.work', ['kind' => auth()->user()->allows('leads.reminders') ? 'Reminder' : 'Follow-up']),
      'Call Details' => route('dashboard.metric', ['metric' => 'outgoing-calls']),
    ];
    ?>
    <section class="dash-summary-grid" data-dashboard-summary aria-label="Business overview">
    @foreach($groups as $title=>$items)@php($orderedItems = collect($items)->filter(fn($count) => $count !== null && (int)$count > 0)->union(collect($items)->reject(fn($count) => $count !== null && (int)$count > 0)))<article class="dash-summary" data-summary-chart><header><h2>{{ $title }}</h2><span class="dash-card-icon"><x-icon name="{{ ['Total Leads'=>'users','Total Tasks'=>'task-list','Opportunities'=>'box','Reminders'=>'bell','Call Details'=>'call-log'][$title] }}" size="17"/></span></header><div class="dash-summary-chart"><div class="dash-summary-pie" data-dashboard-pie role="group" aria-label="{{ $title }} chart"><svg viewBox="0 0 120 120" data-dashboard-pie-slices></svg><span><strong data-dashboard-pie-total>0</strong><small>Total</small></span></div></div><button type="button" class="dash-summary-values-toggle" data-summary-values-toggle aria-expanded="false" aria-controls="dash-summary-values-{{ $loop->index }}"><span>Show values</span></button><ul id="dash-summary-values-{{ $loop->index }}" class="dash-summary-values">@foreach($orderedItems as $label=>$count)<li>@if($count !== null)<a class="dash-metric-link" href="{{ route('dashboard.metric', ['metric'=>\Illuminate\Support\Str::slug($label)]) }}"><span>{{ $label }}</span><span class="dash-count dash-summary-count">{{ (int)$count }}</span></a>@else<span>{{ $label }}</span><span class="dash-count dash-summary-count">0</span>@endif</li>@endforeach</ul>
    <a class="dash-planned" href="{{ $viewAllUrls[$title] }}">View all →</a></article>@endforeach
    </section>
    @if(auth()->user()->allows('leads.view'))<section class="dash-contacts" data-contacts-expanded="false" aria-labelledby="contacts-title"><header class="dash-contacts-toolbar"><div class="dash-contacts-title"><h2 id="contacts-title" data-contacts-heading>Total leads</h2><span class="dash-count">{{ $recentLeads?->total() ?? 0 }}</span>@if($untouchedCount)<span class="dash-untouched-badge">{{ $untouchedCount }} untouched</span>@endif @if($touchedCount)<span class="dash-touched-badge">{{ $touchedCount }} opened</span>@endif<button type="button" class="dash-contacts-expand" data-contacts-expand aria-controls="contacts-table" aria-expanded="false" aria-label="Expand total leads list"><span data-contacts-expand-label>Expand list</span><span class="dash-expand-chevron" aria-hidden="true"><x-icon name="chevron" size="15"/></span></button></div>

    <div class="dash-contact-actions">@if(auth()->user()->allows('leads.create'))<button type="button" class="dash-make-call" data-quick-call-open aria-haspopup="dialog"><x-icon name="phone" size="16"/>Make Call</button><a class="dash-add" href="{{ route('leads.create') }}"><x-icon name="plus" size="16"/>Add New Lead</a>@endif <a href="{{ route('leads.index',['view'=>'extended']) }}">Extended View</a><button type="button" class="dash-multi-button" data-contact-multi-open disabled aria-haspopup="dialog">Multi <span data-contact-selected-count>0</span></button><a class="dash-export-icon" href="{{ route('dashboard.contacts.export', request()->only('filters')) }}" data-contacts-export-url="{{ route('dashboard.contacts.export') }}" download aria-label="Download Excel backup" title="Download Excel backup"><x-icon name="download" size="19"/></a></div></header>
    @if(auth()->user()->allows('leads.delete'))<button type="button" class="dash-archive-untouched dash-archive-untouched-link" data-contact-archive-filter aria-pressed="false"><span aria-hidden="true">▶</span> <span data-contact-archive-label>Archive untouched leads</span></button>@endif
    <form method="GET" id="dashboard-contact-filters" class="dash-column-filters" data-auto-contact-filters aria-label="Filter contacts">
    <input type="hidden" name="per_page" value="{{ request('per_page',25) }}">
    <p class="dash-filter-status" data-contact-filter-status role="status" aria-live="polite"></p>
    <div class="dash-table-scroll" id="contacts-table" tabindex="0" role="region" aria-label="Contacts table"><table class="dash-table"><thead><tr class="dash-table-filter-row">
  <th scope="col" class="dash-contact-select-column"><label class="dash-select-all"><input type="checkbox" data-contact-select-all aria-label="Select all visible leads"><span class="sr-only">Select all visible leads</span></label></th>
  @foreach(['name'=>'Contacts','company'=>'Company','stage'=>'Stage','phone'=>'Number','call'=>null,'location'=>'Location','source'=>'Source','category'=>'Category','owner'=>'Lead Owner','added'=>'Added','score'=>'Score','followups'=>'Follow up Counts'] as $key=>$label)
    <th scope="col">
      @if($label)
        <label class="dash-table-filter">
          <span class="sr-only">Filter {{ $label }}</span>
          <input name="filters[{{ $key }}]" value="{{ request('filters.'.$key) }}" type="{{ $key === 'added' ? 'date' : ($key === 'score' || $key === 'followups' ? 'number' : 'search') }}" @if($key === 'score') min="0" max="100" @elseif($key === 'followups') min="0" @endif placeholder="{{ $key === 'added' ? 'Added date' : 'Filter '.$label }}" autocomplete="off">
        </label>
        @if($key === 'name')
          <a href="{{ route('home') }}" class="dash-clear-filters" @if(!request()->filled('filters')) hidden @endif>Clear</a>
        @endif
      @endif
    </th>
  @endforeach
</tr></thead><tbody>@forelse($recentLeads ?? [] as $lead)<tr class="dash-contact-row" data-contact-id="{{ $lead->id }}" data-contact-touched="{{ $lead->owner_id !== null || $lead->contact_saved_at || $lead->has_been_touched ? 'true' : 'false' }}" data-contact-untouched="{{ $lead->owner_id === null && !$lead->contact_saved_at && !$lead->has_been_touched ? 'true' : 'false' }}"><td class="dash-contact-select-column"><input type="checkbox" class="dash-contact-select" data-contact-select value="{{ $lead->id }}" aria-label="Select {{ $lead->name }}"></td><td><a data-contact-lead-link href="{{ route('leads.show',['lead'=>$lead,'from'=>'dashboard']) }}">{{ $lead->name }}</a>@if($lead->has_been_touched)<small class="dash-touched-badge">Touched</small>@elseif($lead->contact_saved_at)<small style="display:block">Saved contact</small>@elseif($lead->owner_id===null)<small class="dash-untouched-badge">Untouched</small>@else<small style="display:block">Assigned lead</small>@endif @if($lead->repeat_matches_count)<small style="display:block">Received again · {{ $lead->repeat_matches_count }} earlier</small>@endif</td><td>{{ $lead->company ?: '—' }}</td><td>{{ $lead->stage }}</td><td>{{ $lead->phone ?: '—' }}</td><td>@if(filled($lead->phone))<button type="button" class="dash-row-call" data-dashboard-call-open data-call-name="{{ $lead->name }}" data-call-phone="{{ preg_replace('/(?!^)\+/', '', preg_replace('/[^\d+]/','',$lead->phone)) }}" data-call-start-url="{{ route('leads.call.start',$lead) }}" data-call-qr-url="{{ route('leads.call.qr',$lead) }}" data-call-outcome-url="{{ route('leads.call.outcome',$lead) }}" aria-label="Call {{ $lead->name }}"><x-icon name="phone" size="17"/></button>@else—@endif</td><td>{{ $lead->location ?: '—' }}</td><td>{{ $lead->source ?: '—' }}</td><td>{{ $lead->category ?: '—' }}</td><td>@if(auth()->user()->isAdmin())<button type="button" class="lead-owner-history-trigger" data-lead-history-url="{{ route('leads.history',$lead) }}" aria-haspopup="dialog" title="View full lead history">{{ $lead->owner?->name ?? 'Unassigned' }}</button>@else{{ $lead->owner?->name ?? 'Unassigned' }}@endif</td><td>{{ $lead->created_at->format('d M Y') }}</td><td>{{ $scoreSettings?->score($lead) ?? 0 }} / 100</td><td>{{ $lead->follow_up_count }}</td></tr>@empty<tr><td colspan="13"><div class="dash-empty"><span class="dash-empty-icon"><x-icon name="users" size="28"/></span><h3>Leads and contacts will appear here</h3><p>Incoming inquiries from connected platforms and saved contacts appear in this table.</p></div></td></tr>@endforelse</tbody></table></div>
    </form>
    <div class="dash-table-bottom"><span>{{ $recentLeads?->total() ?? 0 }} leads and contacts</span>@if($recentLeads)<nav class="dash-contact-pages" aria-label="Contact pages">@if($recentLeads->previousPageUrl())<a href="{{ $recentLeads->previousPageUrl() }}">Previous</a>@endif<span>Page {{ $recentLeads->currentPage() }} of {{ $recentLeads->lastPage() }}</span>@if($recentLeads->nextPageUrl())<a href="{{ $recentLeads->nextPageUrl() }}">Next</a>@endif</nav>@endif</div></section>@endif
    <section class="dash-calendar" id="dashboard-calendar" data-calendar-url="{{ route('dashboard.calendar') }}" aria-label="Work calendar">
      <header class="calendar-header"><div><h2>Work calendar</h2><p>See assignments, scheduled work, saved contacts and CRM activity by date.</p></div><div class="calendar-controls">@if(auth()->user()->isAdmin())<button type="button" class="button primary" data-calendar-add>+ Add work</button>@endif<button type="button" class="button secondary" data-calendar-today>Today</button><button type="button" class="button secondary" data-calendar-prev aria-label="Previous period">←</button><button type="button" class="button secondary" data-calendar-next aria-label="Next period">→</button><strong data-calendar-title></strong></div></header>
      <div class="calendar-layout"><aside class="calendar-filters"><label>User<select data-calendar-user>@if(auth()->user()->isAdmin())<option value="">All users</option>@endif @foreach($calendarUsers as $person)<option value="{{ $person->id }}" @selected(!auth()->user()->isAdmin() && $person->id===auth()->id())>{{ $person->name }}</option>@endforeach</select></label><fieldset><legend><label class="calendar-select-all"><input type="checkbox" data-calendar-all checked>Show on calendar</label></legend>@foreach(['Task','Follow-up','Reminder','Lead','Change','Loading','Payment','Activity'] as $kind)<label><input type="checkbox" value="{{ $kind }}" data-calendar-kind @checked($kind !== 'Activity')><span class="calendar-dot calendar-{{ \Illuminate\Support\Str::slug($kind) }}"></span>{{ $kind }}</label>@endforeach</fieldset></aside>
      <div class="calendar-main"><div class="calendar-view-toolbar"><nav class="calendar-views" aria-label="Calendar view">@foreach(['month'=>'Month','week'=>'Week','day'=>'Day','list'=>'List'] as $view=>$label)<button type="button" data-calendar-view="{{ $view }}" @if($view==='month') aria-pressed="true" @else aria-pressed="false" @endif>{{ $label }}</button>@endforeach</nav><label class="calendar-list-date" data-calendar-list-date hidden>Find date <input type="date" data-calendar-date aria-label="Find date in list"></label></div><p class="calendar-status" data-calendar-status role="status">Loading calendar…</p><div data-calendar-grid></div></div></div>
    </section>
    <dialog class="calendar-detail" id="calendar-detail"><div class="calendar-detail-content"><button type="button" class="calendar-detail-close" data-calendar-close aria-label="Close details">×</button><span data-calendar-detail-kind></span><h2 data-calendar-detail-title></h2><dl><div><dt>Date and time</dt><dd data-calendar-detail-date></dd></div><div><dt>Type</dt><dd data-calendar-detail-lead></dd></div><div><dt>User</dt><dd data-calendar-detail-person></dd></div><div><dt>Status</dt><dd data-calendar-detail-status></dd></div></dl><p data-calendar-detail-text></p><a class="button primary" data-calendar-detail-link href="#">Open related page</a></div></dialog>
    @if(auth()->user()->isAdmin())<dialog class="calendar-detail calendar-add-dialog" id="calendar-add-dialog" data-lead-search-url="{{ route('dashboard.calendar.leads') }}"><form method="POST" data-calendar-add-form data-action-template="{{ route('leads.work.store',['lead'=>'LEAD_ID']) }}" class="calendar-detail-content">@csrf<button type="button" class="calendar-detail-close" data-calendar-add-close aria-label="Close">×</button><h2>Add scheduled work</h2><p>Choose a lead, then plan a task, follow-up or reminder.</p><label>Find lead<input type="search" data-calendar-lead-search placeholder="Type at least 2 letters" autocomplete="off"></label><label>Lead *<select data-calendar-lead-results required><option value="">Search and select a lead</option></select></label><label>Type<select name="kind" required><option>Task</option><option>Follow-up</option><option>Reminder</option></select></label><label>Title *<input name="title" required maxlength="255"></label><label>Due date and time (IST) *<input name="due_at" type="datetime-local" required></label><label>Assign to *<select name="assigned_to" required>@foreach($calendarUsers->filter(fn($person)=>$person->allows('leads.view') && ($person->allows('leads.tasks') || $person->allows('leads.followups') || $person->allows('leads.reminders'))) as $person)<option value="{{ $person->id }}" data-work-kinds="{{ collect(['Task','Follow-up','Reminder'])->filter(fn($kind)=>$person->allows(\App\Models\LeadWorkItem::permissionForKind($kind)))->implode(',') }}" @selected($person->id===auth()->id())>{{ $person->name }}</option>@endforeach</select></label><label>Priority<select name="priority"><option>Normal</option><option>High</option><option>Low</option></select></label><label>Notes<textarea name="notes" rows="3" maxlength="10000"></textarea></label><button class="button primary">Save work</button></form></dialog>@endif
    <x-footer/>
</main>
</div>
@if(auth()->user()->isAdmin())<dialog class="lead-owner-history-dialog" id="lead-owner-history" aria-labelledby="lead-owner-history-title"><div data-lead-history-body></div></dialog>@endif
<dialog class="dashboard-call-dialog" data-dashboard-call-dialog aria-labelledby="dashboard-call-title">
    <div class="dashboard-call-card">
        <button type="button" class="dashboard-call-close" data-dashboard-call-close aria-label="Close call window">×</button>
        <a class="dashboard-call-now" data-dashboard-call-link href="#"><x-icon name="phone" size="20"/></a>
        <h2 id="dashboard-call-title" data-dashboard-call-title>Call contact</h2>
        <details class="dashboard-call-help"><summary>Need help calling / connect mobile</summary><p>Connect your mobile with Windows Phone Link, or scan the QR code below.</p></details>
        <p class="dashboard-call-scan">Scan with your phone to call.</p>
        <img class="dashboard-call-qr" data-dashboard-call-qr alt="" width="150" height="150">
        <a class="dashboard-call-number" data-dashboard-call-number href="#"></a>
        <form method="POST" data-dashboard-call-form>@csrf
            <input type="hidden" name="outcome" value=""><input type="hidden" name="request_key" value=""><input type="hidden" name="dashboard" value="1">
            <div class="dashboard-call-outcomes" role="group" aria-label="Call outcome"><button type="button" class="button secondary" data-dashboard-call-answered aria-pressed="false" aria-expanded="false">Call Answered</button><button type="button" class="button secondary" data-dashboard-call-unanswered aria-pressed="false" aria-expanded="false">Call Unanswered</button></div>
            <div class="dashboard-call-notes" data-dashboard-call-notes hidden><label><span data-dashboard-call-details-label>Call details *</span><textarea name="details" rows="3" maxlength="20000"></textarea></label><div class="dashboard-call-form-actions"><button type="submit" class="button primary">Submit</button><button type="submit" name="stay_dashboard" value="1" class="button secondary" data-dashboard-call-exit hidden>Exit</button></div><p data-dashboard-call-note-help></p></div>
        </form>
    </div>
</dialog>
@if(auth()->user()->allows('leads.create'))
<dialog class="dashboard-quick-call-dialog" data-quick-call-dialog data-lead-create-url="{{ route('leads.create') }}" aria-labelledby="quick-call-title">
    <div class="dashboard-quick-call-card">
        <button type="button" class="dashboard-quick-call-close" data-quick-call-close aria-label="Close call window">×</button>
        <span class="dashboard-quick-call-icon"><x-icon name="phone" size="20"/></span>
        <h2 id="quick-call-title">Make Call</h2>
        <p>Enter a mobile number and tap Call.</p>
        <label class="dashboard-quick-call-number"><span>Mobile number</span><input type="tel" inputmode="tel" autocomplete="tel" data-quick-call-number placeholder="Enter mobile number" maxlength="24" required></label>
        <div class="dashboard-dial-pad" aria-label="Phone dial pad">
            @foreach(['1','2','3','4','5','6','7','8','9','+','0','⌫'] as $digit)<button type="button" data-dial-key="{{ $digit }}" aria-label="{{ $digit === '⌫' ? 'Delete last digit' : 'Enter '.$digit }}">{{ $digit }}</button>@endforeach
        </div>
        <button type="button" class="button primary dashboard-quick-call-button" data-quick-call-start><x-icon name="phone" size="17"/>Call</button>
        <p class="dashboard-quick-call-status" data-quick-call-status role="status"></p>
        <div class="dashboard-quick-call-actions" data-quick-call-actions hidden><button type="button" class="button secondary" data-quick-call-abort>Abort</button><a class="button primary" data-quick-call-lead href="{{ route('leads.create') }}">Make Lead</a></div>
    </div>
</dialog>
@endif
<dialog class="dash-multi-dialog" data-contact-multi-dialog aria-labelledby="dash-multi-title">
  <div class="dash-multi-card">
    <div class="dash-multi-heading"><div><p class="dash-multi-eyebrow">Selected contacts</p><h2 id="dash-multi-title">Multi actions</h2><p><span data-contact-multi-count>0</span> lead(s) selected</p></div><button type="button" data-contact-multi-close aria-label="Close multi actions">×</button></div>
    <div data-multi-menu>
    <p class="dash-multi-help">Choose an action. Every completed action is recorded against each selected lead.</p>
    @php($multiActions = collect(['call'=>'Call','email'=>'Email','whatsapp'=>'WhatsApp','sms'=>'SMS','reminder'=>'Set reminder','note'=>'Add Notes','opportunity'=>'Create opportunity','contact'=>'Add to contact','list'=>'Add to List/Lists','user'=>'Change user','stage'=>'Change stage','category'=>'Change category','delete'=>'Delete','feedback'=>'Request feedback'])->filter(fn($label,$key) => match($key) {
      'email' => auth()->user()->allows('leads.email'),
      'call','whatsapp','sms','note' => auth()->user()->allows('leads.activity'),
      'reminder' => auth()->user()->allows('leads.reminders'),
      'feedback' => auth()->user()->allows('leads.followups'),
      'opportunity','stage' => auth()->user()->allows('leads.stage'),
      'contact','category' => auth()->user()->allows('leads.edit'),
      'list' => auth()->user()->allows('leads.lists'),
      'user' => auth()->user()->allows('leads.assign'),
      'delete' => auth()->user()->allows('leads.delete'),
      default => false,
    }))
    <div class="dash-multi-options" role="list" aria-label="Available multi actions">
      @foreach($multiActions as $key=>$action)
        <button type="button" role="listitem" class="dash-multi-option" data-contact-multi-option="{{ $key }}" data-multi-label="{{ $action }}">{{ $action }}<span aria-hidden="true">→</span></button>
      @endforeach
    </div>
    </div>
    <form class="dash-multi-form" action="{{ route('dashboard.contacts.bulk-action') }}" method="POST" data-multi-form hidden>@csrf
      <input type="hidden" name="action" value="">
      <div class="dash-multi-form-title"><button type="button" data-multi-back>← Back</button><strong data-multi-form-label></strong></div>
      <div class="dash-multi-fields" data-fields="call"><label>Call note (optional)<textarea name="call_details" rows="2" maxlength="10000" placeholder="Reason or talking points"></textarea></label><p>Creates a lead-wise call record and opens each available phone number.</p></div>
      <div class="dash-multi-fields" data-fields="email"><label>Subject<input name="email_subject" maxlength="255"></label><label>Message<textarea name="email_message" rows="3" maxlength="20000"></textarea></label><p>Your email app opens for each valid contact; CRM stores a prepared-action record.</p></div>
      <div class="dash-multi-fields" data-fields="whatsapp sms"><label>Message<textarea name="message" rows="3" maxlength="2000"></textarea></label><p>Each valid contact opens individually so you can review before sending.</p></div>
      <div class="dash-multi-fields" data-fields="reminder feedback"><label>Title<input name="work_title" maxlength="255"></label><label>Date & time<input name="due_at" type="datetime-local"></label><label>Details (optional)<textarea name="work_details" rows="2" maxlength="10000"></textarea></label></div>
      <div class="dash-multi-fields" data-fields="note"><label>Title (optional)<input name="note_title" maxlength="255"></label><label>Note<textarea name="note_details" rows="3" maxlength="20000"></textarea></label></div>
      <div class="dash-multi-fields" data-fields="opportunity"><label>Opportunity stage<select name="opportunity_stage">@foreach(array_slice(\App\Models\Lead::STAGES,1,5) as $stage)<option>{{ $stage }}</option>@endforeach</select></label><label>Estimated value<input name="value" type="number" min="0" step="0.01"></label><label>Details (optional)<textarea name="opportunity_details" rows="2" maxlength="10000"></textarea></label></div>
      <div class="dash-multi-fields" data-fields="contact"><p>Selected leads will be saved as contacts. Leads already saved are left unchanged.</p></div>
      @if(auth()->user()->allows('leads.lists'))<div class="dash-multi-fields" data-fields="list"><label>List<select name="group_id"><option value="">Choose a list</option>@foreach($multiLists as $list)<option value="{{ $list->id }}">{{ $list->name }}</option>@endforeach</select></label>@if($multiLists->isEmpty())<p>Create a list in List Manager first.</p>@endif</div>@endif
      <div class="dash-multi-fields" data-fields="user"><label>Assign to<select name="owner_id"><option value="">Choose a user</option>@foreach($multiUsers as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></label></div>
      <div class="dash-multi-fields" data-fields="stage"><label>Stage<select name="stage">@foreach(\App\Models\Lead::STAGES as $stage)<option>{{ $stage }}</option>@endforeach</select></label></div>
      <div class="dash-multi-fields" data-fields="category"><label>Category<input name="category" maxlength="100" placeholder="Enter category"></label></div>
      <div class="dash-multi-fields dash-multi-danger" data-fields="delete"><p>Selected leads will move to trash. Type <strong>DELETE</strong> to confirm.</p><label>Confirmation<input name="confirmation" autocomplete="off"></label></div>
      <output class="dash-multi-status" data-multi-status aria-live="polite"></output>
      <div class="dash-multi-links" data-multi-links hidden></div>
      <div class="dash-multi-actions"><button type="button" class="button secondary" data-contact-multi-close>Cancel</button><button type="submit" class="button primary" data-multi-submit>Apply to <span data-contact-multi-count>0</span></button></div>
    </form>
  </div>
</dialog>
<dialog class="calculator-dialog" id="calculator-dialog" aria-labelledby="calculator-title">
    <div class="calculator-heading"><h2 id="calculator-title">Calculator</h2><button type="button" data-calculator-close aria-label="Close calculator">×</button></div>
    <output class="calculator-display" data-calculator-display aria-live="polite">0</output>
    <div class="calculator-keys">
        <button type="button" data-calculator-action="clear">C</button><button type="button" data-calculator-action="backspace" aria-label="Backspace">⌫</button><button type="button" data-calculator-action="percent">%</button><button type="button" class="is-operator" data-calculator-operator="/">÷</button>
        <button type="button" data-calculator-digit="7">7</button><button type="button" data-calculator-digit="8">8</button><button type="button" data-calculator-digit="9">9</button><button type="button" class="is-operator" data-calculator-operator="*">×</button>
        <button type="button" data-calculator-digit="4">4</button><button type="button" data-calculator-digit="5">5</button><button type="button" data-calculator-digit="6">6</button><button type="button" class="is-operator" data-calculator-operator="-">−</button>
        <button type="button" data-calculator-digit="1">1</button><button type="button" data-calculator-digit="2">2</button><button type="button" data-calculator-digit="3">3</button><button type="button" class="is-operator" data-calculator-operator="+">+</button>
        <button type="button" data-calculator-action="negate">±</button><button type="button" data-calculator-digit="0">0</button><button type="button" data-calculator-action="decimal">.</button><button type="button" class="is-equals" data-calculator-action="equals">=</button>
    </div>
</dialog>
<x-toasts/>
<style>
/* Keep the dashboard overview compact and aligned in both list and pie modes. */
.dashboard-page .dash-chart-toggle {
    width: 30px;
    height: 28px;
    min-height: 28px;
    padding: 0;
    justify-content: center;
    border-radius: 8px;
    transform: translateY(3px);
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary {
    height: 306px;
    min-height: 306px;
    grid-template-rows: auto minmax(0, 1fr) auto auto;
    overflow: hidden;
}
.dashboard-page .dash-summary-grid.is-chart-mode {
    align-items: start;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-chart {
    padding: 12px 12px 0;
    align-content: center;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-pie {
    width: 182px;
    height: 182px;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-pie > span {
    width: 84px;
    height: 84px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-pie strong { font-size: 22px; }
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-pie text {
    fill: #fff;
    font-family: inherit;
    font-weight: 800;
    pointer-events: none;
    paint-order: stroke;
    stroke: rgba(18, 36, 58, .28);
    stroke-width: .55px;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-values-toggle {
    grid-row: 3;
    align-self: end;
    margin: 8px auto;
    transform: none;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary ul.dash-summary-values {
    min-height: 0;
    overflow-y: auto;
    align-content: start;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary.is-values-expanded {
    height: auto;
    min-height: 306px;
    grid-template-rows: auto auto auto auto auto;
    overflow: visible;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary.is-values-expanded .dash-summary-chart {
    grid-row: 2;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary.is-values-expanded .dash-summary-values-toggle {
    grid-row: 3;
    margin: 8px auto 0;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary.is-values-expanded ul.dash-summary-values {
    grid-row: 4;
    max-height: 200px;
    margin: 8px 0 10px;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary .dash-planned {
    display: none;
    margin: 0 auto 12px;
    padding: 0;
    color: var(--primary,#0645ad);
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
}
.dashboard-page .dash-summary-grid.is-chart-mode .dash-summary.is-values-expanded .dash-planned {
    display: block;
    grid-row: 5;
}
@media (max-width: 760px) {
    .dashboard-page .dash-summary-grid.is-chart-mode .dash-summary { height: 294px; min-height: 294px; }
    .dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-pie { width: 118px; height: 118px; }
    .dashboard-page .dash-summary-grid.is-chart-mode .dash-summary-pie > span { width: 64px; height: 64px; }
    .dashboard-page .dash-summary-grid.is-chart-mode .dash-summary.is-values-expanded { min-height: 294px; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-dashboard-chart-toggle]');
    const summary = document.querySelector('[data-dashboard-summary]');
    if (!toggle || !summary) return;
    const colors = ['#1267c5', '#7c3aed', '#159b70', '#e88916', '#e14857', '#0b8f94', '#4158c8', '#9a5a16'];
    const svgNamespace = 'http://www.w3.org/2000/svg';
    const pointAt = (angle) => {
        const radians = (angle - 90) * Math.PI / 180;
        return { x: 60 + 60 * Math.cos(radians), y: 60 + 60 * Math.sin(radians) };
    };
    const pointAtRadius = (angle, radius) => {
        const radians = (angle - 90) * Math.PI / 180;
        return { x: 60 + radius * Math.cos(radians), y: 60 + radius * Math.sin(radians) };
    };
    const wedgePath = (startAngle, endAngle) => {
        if (endAngle - startAngle >= 359.99) return 'M 60 60 m -60 0 a 60 60 0 1 0 120 0 a 60 60 0 1 0 -120 0';
        const start = pointAt(startAngle);
        const end = pointAt(endAngle);
        return `M 60 60 L ${start.x} ${start.y} A 60 60 0 ${endAngle - startAngle > 180 ? 1 : 0} 1 ${end.x} ${end.y} Z`;
    };
    const renderCharts = () => summary.querySelectorAll('[data-dashboard-pie]').forEach((pie) => {
        const card = pie.closest('[data-summary-chart]');
        const entries = [...card.querySelectorAll('.dash-summary-values li')].map((item, index) => ({
            label: item.querySelector('span')?.textContent.trim() || `Value ${index + 1}`,
            value: Number(item.querySelector('.dash-summary-count')?.textContent.trim()) || 0,
        }));
        const total = entries.reduce((sum, entry) => sum + entry.value, 0);
        const svg = pie.querySelector('[data-dashboard-pie-slices]');
        svg.replaceChildren();
        pie.querySelector('[data-dashboard-pie-total]').textContent = total;
        pie.setAttribute('aria-label', total ? `${pie.closest('.dash-summary').querySelector('h2').textContent}: ${total} total. Hover slices for category values.` : 'No values to show.');
        if (!total) {
            const circle = document.createElementNS(svgNamespace, 'circle');
            circle.setAttribute('cx', '60'); circle.setAttribute('cy', '60'); circle.setAttribute('r', '60'); circle.setAttribute('fill', '#dfe7f2');
            svg.append(circle);
            return;
        }
        let position = 0;
        entries.forEach((entry, index) => {
            if (!entry.value) return;
            const end = position + (entry.value / total) * 360;
            const sweep = end - position;
            const path = document.createElementNS(svgNamespace, 'path');
            path.setAttribute('d', wedgePath(position, end));
            path.setAttribute('fill', colors[index % colors.length]);
            path.setAttribute('tabindex', '0');
            path.setAttribute('aria-label', `${entry.label}: ${entry.value}`);
            const title = document.createElementNS(svgNamespace, 'title');
            title.textContent = `${entry.label}: ${entry.value}`;
            path.append(title);
            svg.append(path);

            const labelPoint = pointAtRadius(position + sweep / 2, 47);
            const label = document.createElementNS(svgNamespace, 'text');
            label.setAttribute('x', labelPoint.x);
            label.setAttribute('y', labelPoint.y);
            label.setAttribute('text-anchor', 'middle');
            label.setAttribute('dominant-baseline', 'central');
            label.setAttribute('transform', `rotate(90 ${labelPoint.x} ${labelPoint.y})`);
            label.setAttribute('font-size', sweep < 12 ? '6' : sweep < 24 ? '8' : '10');
            label.textContent = entry.value;
            svg.append(label);
            position = end;
        });
    });
    const chartStorageKey = 'hauldesk.dashboard.chartMode';
    const setChartMode = (enabled, persist = false) => {
        summary.classList.toggle('is-chart-mode', enabled);
        toggle.setAttribute('aria-pressed', String(enabled));
        const label = enabled ? 'Show list view' : 'Show pie chart view';
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);
        if (persist) {
            try { localStorage.setItem(chartStorageKey, enabled ? 'pie' : 'list'); } catch (_) {}
        }
    };
    renderCharts();
    try { setChartMode(localStorage.getItem(chartStorageKey) === 'pie'); } catch (_) { setChartMode(false); }
    toggle.addEventListener('click', () => setChartMode(!summary.classList.contains('is-chart-mode'), true));
    summary.querySelectorAll('[data-summary-values-toggle]').forEach((button) => button.addEventListener('click', () => {
        const card = button.closest('[data-summary-chart]');
        summary.querySelectorAll('[data-summary-chart].is-values-expanded').forEach((openCard) => {
            if (openCard === card) return;
            openCard.classList.remove('is-values-expanded');
            const openButton = openCard.querySelector('[data-summary-values-toggle]');
            openButton.setAttribute('aria-expanded', 'false');
            openButton.querySelector('span').textContent = 'Show values';
        });
        const expanded = card.classList.toggle('is-values-expanded');
        button.setAttribute('aria-expanded', String(expanded));
        button.querySelector('span').textContent = expanded ? 'Hide values' : 'Show values';
    }));
});
</script>
</body>
</html>
