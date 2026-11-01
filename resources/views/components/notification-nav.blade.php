<nav class="access-shortcuts" aria-label="Notification tools">
    <a class="button {{ request()->routeIs('notifications.index') ? 'primary' : 'secondary' }}" href="{{ route('notifications.index') }}">Current Notification</a>
    @if(auth()->user()->allows('notifications.send'))<a class="button {{ request()->routeIs('broadcasts.compose') ? 'primary' : 'secondary' }}" href="{{ route('broadcasts.compose') }}">Send notification</a>@endif
    @if(auth()->user()->allows('notifications.view'))<a class="button {{ request()->routeIs('broadcasts.index') ? 'primary' : 'secondary' }}" href="{{ route('broadcasts.index') }}">Sent notifications</a>@endif
</nav>
