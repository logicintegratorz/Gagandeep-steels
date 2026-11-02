<nav class="access-shortcuts" aria-label="Notification tools">
    <a class="button {{ request()->routeIs('notifications.index') ? 'primary' : 'secondary' }}" href="{{ route('notifications.index') }}">Current Notification</a>
    @if(auth()->user()->isAdmin())<a class="button {{ request()->routeIs('broadcasts.compose') ? 'primary' : 'secondary' }}" href="{{ route('broadcasts.compose') }}">Send notification</a><a class="button {{ request()->routeIs('broadcasts.index') ? 'primary' : 'secondary' }}" href="{{ route('broadcasts.index') }}">Sent notifications</a>@endif
</nav>
