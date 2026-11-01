<x-app-layout title="List Manager">
@include('leads.nav')
<section class="panel lead-panel list-page list-manager">
 <div class="lead-list-heading">
  <div><h2>List Manager</h2><p>Keep contacts in lists. Open a list to manage its contacts, message, and schedule.</p></div>
  <div class="list-heading-actions"><span class="lead-list-count">{{ $lists->count() }} {{ \Illuminate\Support\Str::plural('list',$lists->count()) }}</span>@if(auth()->user()->allows('leads.lists'))<button type="button" class="button primary" data-open-list-dialog>+ New List</button>@endif</div>
 </div>
 <div class="list-manager-layout">
  <aside class="list-manager-recent" aria-label="Recent lists">
   <h3>Recent lists</h3>
   @forelse($lists->take(5) as $recent)<a href="{{ route('leads.lists.show',$recent) }}"><span>{{ $recent->name }}</span><small>{{ $recent->members_count }} contacts</small></a>@empty<p>No lists yet.</p>@endforelse
  </aside>
  <div class="list-manager-main">
   <div class="list-manager-toolbar"><div><h3>All lists</h3><p>Choose a list to open it, or use ⚙ for more actions.</p></div><div class="list-manager-filters"><label>Search lists<input type="search" placeholder="Search by list name" data-list-search></label><label>Status<select data-list-status><option value="all">All</option><option value="active">Active</option><option value="inactive">Deactivated</option></select></label></div></div>
   <div class="list-manager-table" role="table" aria-label="All lists">
    <div class="list-manager-table-head" role="row"><span role="columnheader">List name</span><span role="columnheader">Contacts</span><span role="columnheader">Status</span><span role="columnheader">Updated</span><span role="columnheader">Actions</span></div>
    @foreach($lists as $list)
    <div class="list-manager-row" role="row" data-list-row data-list-name="{{ mb_strtolower($list->name) }}" data-list-status="{{ $list->active ? 'active' : 'inactive' }}">
     <div role="cell"><a class="list-manager-name" href="{{ route('leads.lists.show',$list) }}">{{ $list->name }}</a><small>{{ \Illuminate\Support\Str::limit($list->description,90) }}</small></div>
     <span role="cell">{{ $list->members_count }}</span><span role="cell"><span class="list-manager-state {{ $list->active ? 'is-active' : 'is-inactive' }}">{{ $list->active ? 'Active' : 'Deactivated' }}</span></span><span role="cell" class="list-manager-date">{{ $list->updated_at?->format('d M Y') }}</span>
     <div role="cell" class="list-manager-actions"><details class="list-action-menu"><summary aria-label="Settings for {{ $list->name }}" title="List settings">⚙ <span aria-hidden="true">⌄</span></summary><div class="list-action-popover"><strong>{{ $list->name }}</strong><a href="{{ route('leads.lists.show',$list) }}">Open list</a><a href="{{ route('leads.lists.show',$list).'#list-contacts' }}">View / edit members</a>@if(auth()->user()->allows('leads.broadcast') && $list->active)<button type="submit" form="list-email-{{ $list->id }}">Send email to list</button><button type="submit" form="list-whatsapp-{{ $list->id }}">Open WhatsApp chats</button>@endif @if(auth()->user()->allows('leads.automations') && $list->active)<a href="{{ route('leads.lists.show',$list).'#list-automation' }}">Schedule messages</a>@endif @if(auth()->user()->allows('leads.lists'))<a href="{{ route('leads.lists.show',[$list,'edit'=>1]).'#list-editor' }}">Rename list</a><button type="submit" form="list-active-{{ $list->id }}">{{ $list->active ? 'Deactivate list' : 'Activate list' }}</button><button class="is-danger" type="submit" form="list-delete-{{ $list->id }}">Remove list</button>@endif</div></details></div>
    </div>
    @endforeach
   </div>
   <p class="lead-empty-state" data-list-empty @if($lists->isNotEmpty()) hidden @endif>{{ $lists->isEmpty() ? 'No lists yet. Use New List to create one.' : 'No lists match this search.' }}</p>
   @foreach($lists as $list)
    @if(auth()->user()->allows('leads.broadcast') && $list->active)<form id="list-email-{{ $list->id }}" method="POST" action="{{ route('leads.lists.send-all',$list) }}" onsubmit="return confirm('Send this list message by email to every visible contact?')">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="channel" value="Email"></form><form id="list-whatsapp-{{ $list->id }}" method="POST" action="{{ route('leads.lists.send-all',$list) }}">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="channel" value="WhatsApp"></form>@endif
    @if(auth()->user()->allows('leads.lists'))<form id="list-active-{{ $list->id }}" method="POST" action="{{ route('leads.lists.active',$list) }}">@csrf</form><form id="list-delete-{{ $list->id }}" method="POST" action="{{ route('leads.lists.destroy',$list) }}" onsubmit="return confirm('Remove this list and its schedules? Contacts remain in CRM.')">@csrf @method('DELETE')</form>@endif
   @endforeach
  </div>
 </div>
</section>
@if(auth()->user()->allows('leads.lists'))
<dialog id="create-list" class="list-create-dialog" aria-labelledby="create-list-title" @if($selectedLead || $errors->any()) data-open-on-load @endif>
 <div class="list-dialog-heading"><div><h2 id="create-list-title">New List</h2><p>Name the list and write the message its contacts will receive.</p></div><button type="button" class="list-dialog-close" data-close-list-dialog aria-label="Close New List">×</button></div>
 <form method="POST" action="{{ route('leads.lists.store') }}" class="list-create">@csrf
  @if($selectedLead)<input type="hidden" name="lead_id" value="{{ $selectedLead->id }}"><div class="list-selected-lead">{{ $selectedLead->name }} will be added automatically.</div>@endif
  <label>List name<input name="name" required maxlength="150" value="{{ old('name') }}" placeholder="For example, Daily price updates" autofocus></label>
  <label>Message<textarea name="description" required maxlength="500" rows="5" placeholder="Write the message you want contacts to receive">{{ old('description') }}</textarea></label>
  <div class="list-dialog-actions"><button type="button" class="button secondary" data-close-list-dialog>Cancel</button><button class="button primary">Create List</button></div>
 </form>
</dialog>
@endif
<script src="{{ asset('js/lead-lists.js').'?v='.filemtime(public_path('js/lead-lists.js')) }}" defer></script>
</x-app-layout>
