@php($activeProductivityModule = $activeProductivityModule ?? $module ?? '')
<nav class="productivity-module-nav" aria-label="Productivity modules">
 @foreach(['reminders'=>['reminders','◷','Reminder'], 'meetings'=>['meetings','♟','Meeting'], 'to-dos'=>['to_dos','☷','To-Do'], 'notes'=>['notes','▮','Note']] as $moduleKey => [$permissionModule, $icon, $label])
  @if(auth()->user()->allows("productivity.$permissionModule.view"))<a class="{{ $activeProductivityModule === $moduleKey ? 'is-active' : '' }}" href="{{ route('productivity.index',$moduleKey) }}"><span>{{ $icon }}</span> {{ $label }}</a>@endif
 @endforeach
</nav>
