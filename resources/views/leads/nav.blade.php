<link rel="stylesheet" href="{{ asset('css/leads.css').'?v='.filemtime(public_path('css/leads.css')) }}">
@php
 $leadNav=request()->routeIs('leads.index','leads.show','leads.edit');
 $pipelineNav=request()->routeIs('leads.pipeline');
 $listsNav=request()->routeIs('leads.lists','leads.lists.*');
 $workNav=request()->routeIs('leads.work','leads.work.*');
 $createNav=request()->routeIs('leads.create');
 $settingsNav=request()->routeIs('leads.settings','leads.settings.*');
 $jobRolesNav=request()->routeIs('leads.job-roles','leads.job-roles.*');
 $connectionsNav=request()->routeIs('leads.connections','leads.connections.*');
@endphp
<nav class="lead-nav" aria-label="Lead workspace">
 <a class="button secondary {{ $leadNav ? 'is-active' : '' }}" href="{{ route('leads.index') }}" @if($leadNav) aria-current="page" @endif>Leads</a>
 <a class="button secondary {{ $pipelineNav ? 'is-active' : '' }}" href="{{ route('leads.pipeline') }}" @if($pipelineNav) aria-current="page" @endif>Pipeline</a>
 <a class="button secondary {{ request()->routeIs('lead-groups.*') ? 'is-active' : '' }}" href="{{ route('lead-groups.index') }}">Lead Lists</a>
 @if(auth()->user()->allows('leads.lists'))<a class="button secondary {{ $listsNav ? 'is-active' : '' }}" href="{{ route('leads.lists') }}" @if($listsNav) aria-current="page" @endif>List Manager</a>@endif
 @if(auth()->user()->allows('leads.tasks') || auth()->user()->allows('leads.followups') || auth()->user()->allows('leads.reminders'))<a class="button secondary {{ $workNav ? 'is-active' : '' }}" href="{{ route('leads.work') }}" @if($workNav) aria-current="page" @endif>Follow-ups, Tasks &amp; Reminders</a>@endif
 @if(auth()->user()->allows('leads.create'))<a class="button secondary {{ $createNav ? 'is-active' : '' }}" href="{{ route('leads.create') }}" @if($createNav) aria-current="page" @endif>Add lead</a>@endif
 @if(auth()->user()->isAdmin())
 <a class="button secondary {{ $jobRolesNav ? 'is-active' : '' }}" href="{{ route('leads.job-roles') }}" @if($jobRolesNav) aria-current="page" @endif>Job Rolls</a>
 <a class="button secondary {{ $settingsNav ? 'is-active' : '' }}" href="{{ route('leads.settings') }}" @if($settingsNav) aria-current="page" @endif>Lead settings</a>
 <a class="button secondary {{ $connectionsNav ? 'is-active' : '' }}" href="{{ route('leads.connections') }}" @if($connectionsNav) aria-current="page" @endif>Lead connections</a>
 @endif
</nav>
@if($errors->any())<div class="lead-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($settingsNav && isset($settings))@include('leads.stage-alert-settings')@endif
