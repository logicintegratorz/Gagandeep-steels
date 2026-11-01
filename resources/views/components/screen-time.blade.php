@auth
    <span id="screen-time-settings" data-heartbeat-url="{{ route('screen-time.heartbeat') }}" data-token="{{ csrf_token() }}" hidden></span>
    <script src="{{ asset('js/screen-time.js').'?v='.filemtime(public_path('js/screen-time.js')) }}" defer></script>
@endauth
