<x-app-layout title="Content Manager">
<link rel="stylesheet" href="{{ asset('css/content-manager.css').'?v='.filemtime(public_path('css/content-manager.css')) }}">
<header class="page-heading content-heading"><div><span class="eyebrow">DOCUMENT LIBRARY</span><h1>Content Manager<span>.</span></h1><p>Save, find and open team documents without unnecessary folders.</p></div></header>

@if($errors->any())<div class="notice error"><strong>Could not save the document.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('success'))<div class="notice success">{{ session('success') }}</div>@endif

<nav class="content-tabs" aria-label="Document categories">
@foreach($tabs as $name)<a href="{{ route('content-manager.index', ['tab'=>$name]) }}" class="{{ $tab === $name ? 'is-active' : '' }}" @if($tab === $name) aria-current="page" @endif>{{ $name }}</a>@endforeach
</nav>

@if(auth()->user()->isAdmin())
<section class="panel content-upload-panel">
  <form method="POST" action="{{ route('content-manager.store') }}" enctype="multipart/form-data" class="content-upload-form">@csrf
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label>Document title *<input name="title" maxlength="255" required value="{{ old('title') }}" placeholder="Example: Spectro report - November"></label>
    <label>Attachment *<input type="file" name="attachment" required accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.webp"><small>PDF, Office, CSV, text or image · maximum 20 MB</small></label>
    <label class="content-notes">Notes (optional)<textarea name="description" rows="2" maxlength="10000" placeholder="Short description or reference">{{ old('description') }}</textarea></label>
    <button class="button primary">Save document</button>
  </form>
</section>
@endif

<section class="panel content-records">
  <form method="GET" action="{{ route('content-manager.index') }}" class="content-filters">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label>Search<input name="q" value="{{ $filters['q'] ?? '' }}" maxlength="150" placeholder="Title, notes or file name"></label>
    <label>From<input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
    <label>To<input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
    <button class="button primary">Filter</button><a class="button secondary" href="{{ route('content-manager.index', ['tab'=>$tab]) }}">Clear</a>
  </form>
  <div class="content-record-heading"><h2>{{ $tab }} documents</h2><span>{{ $documents->total() }} records</span></div>
  <div class="table-scroll"><table class="content-table"><thead><tr><th>Document</th><th>File</th><th>Added by</th><th>Date & time</th><th>Actions</th></tr></thead><tbody>
  @forelse($documents as $document)
    <tr><td><strong>{{ $document->title }}</strong>@if($document->description)<small>{{ $document->description }}</small>@endif</td><td><span>{{ $document->attachment_name }}</span><small>{{ number_format($document->attachment_size / 1024, 1) }} KB</small></td><td>{{ $document->uploader?->name ?? 'Former user' }}</td><td>{{ $document->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td><td><div class="content-actions"><a class="button secondary" href="{{ route('content-manager.open',$document) }}" target="_blank">View</a><a class="button secondary" href="{{ route('content-manager.download',$document) }}">Download</a>@if(auth()->user()->isAdmin())<details><summary class="button secondary">Edit</summary><form method="POST" action="{{ route('content-manager.update',$document) }}" enctype="multipart/form-data">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $document->version }}"><label>Tab<select name="tab">@foreach($tabs as $name)<option @selected($document->tab === $name)>{{ $name }}</option>@endforeach</select></label><label>Title<input name="title" maxlength="255" required value="{{ $document->title }}"></label><label>Notes<textarea name="description" rows="2" maxlength="10000">{{ $document->description }}</textarea></label><label>Replace file<input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.webp"></label><button class="button primary">Update</button></form></details><form method="POST" action="{{ route('content-manager.destroy',$document) }}" onsubmit="return confirm('Delete this document and attachment?')">@csrf @method('DELETE')<input type="hidden" name="version" value="{{ $document->version }}"><button class="button danger">Delete</button></form>@endif</div></td></tr>
  @empty<tr><td colspan="5"><div class="empty"><h3>No {{ strtolower($tab) }} documents yet</h3><p>{{ auth()->user()->isAdmin() ? 'Add the first document using the compact form above.' : 'An administrator has not added a document in this tab yet.' }}</p></div></td></tr>@endforelse
  </tbody></table></div>
  <div class="table-footer"><span>{{ $documents->firstItem() ?? 0 }}–{{ $documents->lastItem() ?? 0 }} of {{ $documents->total() }}</span><div class="pagination">@if($documents->previousPageUrl())<a class="page-button" href="{{ $documents->previousPageUrl() }}">‹</a>@endif<span>Page {{ $documents->currentPage() }} of {{ $documents->lastPage() }}</span>@if($documents->nextPageUrl())<a class="page-button" href="{{ $documents->nextPageUrl() }}">›</a>@endif</div></div>
</section>
</x-app-layout>
