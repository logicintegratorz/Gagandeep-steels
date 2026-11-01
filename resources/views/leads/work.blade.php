@php($taskPage = request()->routeIs('tasks.index'))
<x-app-layout :title="$taskPage ? 'Task' : 'Follow-ups, Tasks & Reminders'">
    @if($taskPage)
        <link rel="stylesheet" href="{{ asset('css/leads.css').'?v='.filemtime(public_path('css/leads.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/tasks.css').'?v='.filemtime(public_path('css/tasks.css')) }}">
        <script src="{{ asset('js/tasks.js').'?v='.filemtime(public_path('js/tasks.js')) }}" defer></script>
    @else
        @include('leads.nav')
    @endif

    <section class="panel lead-panel {{ $taskPage ? 'task-page task-board-page' : '' }}">
        @if($taskPage)
            @include('leads.task-board')
        @else
        <header class="task-page-heading">
            <div><p class="eyebrow">WORKSPACE</p><h1>{{ $taskPage ? 'Tasks' : 'Follow-ups, Tasks & Reminders' }}</h1><p>{{ $taskPage ? 'Create, assign and track work connected to your leads.' : 'Admins assign each action to a user. Your assigned actions appear here.' }}</p></div>
            @if($taskPage && auth()->user()->isAdmin())<button type="button" class="button primary" data-task-open>+ Add task</button>@endif
        </header>
        @if($taskPage && $errors->any())<div class="lead-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form class="lead-filters task-filters">
            @if($taskPage)<input type="hidden" name="kind" value="Task">@endif
            <label>Search<input name="q" value="{{ request('q') }}" placeholder="Task or lead name"></label>
            @unless($taskPage)<label>Type<select name="kind"><option value="">All types</option>@foreach(['Task','Follow-up','Reminder'] as $kind)<option @selected(request('kind')===$kind)>{{ $kind }}</option>@endforeach</select></label>@endunless
            <label>Status<select name="status"><option value="">All statuses</option>@foreach(['Pending','Overdue','Today','Completed','Cancelled'] as $status)<option @selected(request('status','Pending')===$status)>{{ $status }}</option>@endforeach</select></label>
            <button class="button primary">Filter</button>
            @if(request()->filled('q') || request('status','Pending')!=='Pending')<a class="button secondary" href="{{ $taskPage ? route('tasks.index') : route('leads.work') }}">Clear</a>@endif
        </form>
        <div class="task-results">@forelse($items as $item)@include('leads.work-item')@empty<div class="lead-empty-state"><strong>No {{ $taskPage ? 'tasks' : 'work items' }} found</strong><p>{{ $taskPage ? 'Use Add task to plan the next action.' : 'Try another filter.' }}</p></div>@endforelse</div>
        @include('leads.pages',['records'=>$items])
        @endif
    </section>

    @if($taskPage && auth()->user()->allows('leads.tasks'))
        <dialog class="task-dialog" data-task-dialog data-search-url="{{ route('dashboard.calendar.leads') }}">
            <form method="POST" enctype="multipart/form-data" data-task-form data-action-template="{{ route('leads.work.store',['lead'=>'LEAD_ID']) }}">
                @csrf
                <input type="hidden" name="kind" value="Task">
                <header><h2>Add Task</h2><button type="button" class="icon-button" data-task-close aria-label="Close">×</button></header>
                <div class="task-form-grid">
                    <label class="task-subject">Subject: <b>*</b><input name="title" required maxlength="255" placeholder="Enter Subject"></label>
                    <label>Priority:<select name="priority" required><option selected>Low</option><option>Normal</option><option>High</option></select></label>
                    <label>Status:<select disabled><option>New</option></select></label>
                    <label>Label (Optional):<select name="label" aria-label="Optional task label"><option value="">Select...</option><option>Important</option><option>Follow-up</option><option>Internal</option></select></label>
                    <label class="task-recursive">Recursive <span class="task-switch"><input type="checkbox" data-task-recursive><i></i></span><select name="recurrence_type" hidden><option value="">Does not repeat</option><option value="Daily">Daily</option></select><input type="hidden" name="recurring" value="0"></label>
                    <label>Start Date:<input type="datetime-local" data-task-start></label>
                    <label>End Date:<input type="datetime-local" name="due_at" required></label>
                    <label class="task-assign">Assign To: <b>*</b><select name="assigned_to" required @disabled(!auth()->user()->isAdmin())>@foreach($owners->filter(fn($owner)=>$owner->allows('leads.tasks')) as $owner)<option value="{{ $owner->id }}" @selected($owner->id===auth()->id())>{{ $owner->name }}</option>@endforeach</select></label>
                    <label class="task-lead">Related lead: <b>*</b><input type="search" data-task-lead-search placeholder="Search lead or company" autocomplete="off"><select data-task-lead-results required><option value="">Search and select a lead</option></select></label>
                    <div class="task-description"><span>Description:</span><div class="task-rich"><div class="task-toolbar" role="toolbar" aria-label="Description formatting"><button type="button" data-task-command="bold"><b>B</b></button><button type="button" data-task-command="italic"><i>I</i></button><button type="button" data-task-command="underline"><u>U</u></button><button type="button" data-task-command="strikeThrough"><s>S</s></button><button type="button" data-task-command="formatBlock" data-value="pre">{ }</button><button type="button" data-task-command="superscript">X²</button><button type="button" data-task-command="subscript">X₂</button><select data-task-format><option value="p">Normal</option><option value="h3">Heading</option><option value="pre">Code</option></select><button type="button" data-task-command="insertUnorderedList">☷</button><button type="button" data-task-command="insertOrderedList">☷¹</button><button type="button" data-task-command="justifyLeft">≡</button><button type="button" data-task-command="justifyCenter">≡</button><button type="button" data-task-command="justifyRight">≡</button><button type="button" data-task-command="undo">↶</button><button type="button" data-task-command="redo">↷</button></div><div class="task-rich-body" contenteditable="true" data-task-rich data-placeholder="Enter Description"></div></div><textarea name="notes" maxlength="10000" hidden data-task-notes></textarea></div>
                    <label class="task-description">Attachment (Optional):<span class="task-upload">⇩<strong>Drop Files here or click to upload</strong><small>Allowed IMAGES, VIDEOS, PDF, DOC, EXCEL, PPT, TEXT<br>max size of 3 MB</small><input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"></span></label>
                </div>
                <div class="task-add-new"><button type="submit" class="button primary" name="create_another" value="1">+ Add New</button></div>
                <footer><button class="button primary">Submit</button><button type="button" class="button secondary" data-task-close>Cancel</button></footer>
            </form>
        </dialog>
    @endif
    @if($taskPage) @include('leads.task-action-dialogs') @endif
</x-app-layout>
