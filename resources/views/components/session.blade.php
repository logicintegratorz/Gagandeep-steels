@auth
<meta name="csrf-token" content="{{ csrf_token() }}">
<div hidden id="session-settings" data-status-url="{{ route('session.status') }}" data-activity-url="{{ route('session.activity') }}" data-login-url="{{ route('login') }}" data-idle="{{ (int) session('last_activity_at') + 1800 }}" data-midnight="{{ session('login_expires_at') }}" data-server-now="{{ now()->timestamp }}"></div>
<script src="{{ asset('js/session.js') }}" defer></script>
@endauth
