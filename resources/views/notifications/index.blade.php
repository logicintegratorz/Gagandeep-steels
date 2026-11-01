<x-app-layout title="Notifications">
<div class="page-heading"><div><div class="eyebrow">WORKSPACE UPDATES</div><h1>Notifications<span>.</span></h1></div><span class="count">{{ $unread }} unread</span></div>
<x-notification-nav/>
@if(auth()->user()->isAdmin())
<form method="POST" action="{{ route('notifications.sound') }}" style="margin-bottom:20px">@csrf<input type="hidden" name="enabled" value="{{ $soundEnabled ? 0 : 1 }}"><button class="button secondary">Notification sound: {{ $soundEnabled ? 'On — turn off' : 'Off — turn on' }}</button></form>
@endif
<section class="panel">
    <div class="list-heading notification-tools">
        <nav class="notification-tabs" aria-label="Filter notifications">@foreach(['all'=>'All','unread'=>'Unread','read'=>'Read'] as $value=>$label)<a class="button {{ $status === $value ? 'primary' : 'secondary' }}" href="{{ route('notifications.index', ['status'=>$value]) }}" @if($status === $value) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="button secondary" @disabled(!$unread)>Mark all as read</button></form>
    </div>
    <div class="notification-list">
    @forelse($notifications as $notification)
        <article class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
            <span class="notification-symbol"><x-icon name="bell"/></span>
            <div class="notification-copy"><div class="notification-title"><h2>{{ $notification->title }}</h2>@unless($notification->read_at)<span class="status-badge">Unread</span>@endunless</div><p>{{ $notification->message }}</p><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</time>
                <div class="row-actions">@unless($notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="button secondary" aria-label="Mark {{ $notification->title }} as read">Mark as read</button></form>@endunless<form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">@csrf @method('DELETE')<button class="button secondary" aria-label="Dismiss {{ $notification->title }}">Dismiss</button></form></div>
            </div>
        </article>
    @empty
        <div class="empty"><x-icon name="bell" size="32"/><h3>{{ $status === 'unread' ? 'You’re all caught up' : 'No notifications here yet' }}</h3><p>Relevant CRM activity and account updates will appear here.</p></div>
    @endforelse
    </div>
    <div class="table-footer"><span>{{ $notifications->firstItem() ?? 0 }}–{{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} notifications</span><nav class="pagination" aria-label="Notification pages">@if($notifications->previousPageUrl())<a class="page-button" href="{{ $notifications->previousPageUrl() }}" aria-label="Previous page">‹</a>@endif<span>Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}</span>@if($notifications->hasMorePages())<a class="page-button" href="{{ $notifications->nextPageUrl() }}" aria-label="Next page">›</a>@endif</nav></div>
</section>
</x-app-layout>
