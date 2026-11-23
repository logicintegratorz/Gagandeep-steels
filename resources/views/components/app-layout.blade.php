@props(['title', 'bodyClass' => ''])
<!doctype html>
<html lang="en">
<head>
    <script>try{const theme=localStorage.getItem('crm-theme')||'system';document.documentElement.dataset.theme=theme;document.documentElement.dataset.appearance=theme==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):theme}catch(e){document.documentElement.dataset.appearance='light'}</script>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Gagandeep Steels</title>
    <link rel="icon" href="{{ asset('images/gagandeep-steels-logo.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/app.css').'?v='.filemtime(public_path('css/app.css')) }}">
    @if($bodyClass === 'extended-leads')
        <link rel="stylesheet" href="{{ asset('css/extended-leads.css').'?v='.filemtime(public_path('css/extended-leads.css')) }}">
    @endif
    <script src="{{ asset('js/app.js').'?v='.filemtime(public_path('js/app.js')) }}" defer></script>
    <script src="{{ asset('js/ui.js').'?v='.filemtime(public_path('js/ui.js')) }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/feedback.css').'?v='.filemtime(public_path('css/feedback.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/calculator.css').'?v='.filemtime(public_path('css/calculator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css').'?v='.filemtime(public_path('css/theme.css')) }}">
    <script src="{{ asset('js/theme.js').'?v='.filemtime(public_path('js/theme.js')) }}" defer></script>
    <script src="{{ asset('js/calculator.js').'?v='.filemtime(public_path('js/calculator.js')) }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/compact.css').'?v='.filemtime(public_path('css/compact.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css').'?v='.filemtime(public_path('css/dashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-messages.css').'?v='.filemtime(public_path('css/dashboard-messages.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-messages-responsive.css').'?v='.filemtime(public_path('css/dashboard-messages-responsive.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-sent-bulk.css').'?v='.filemtime(public_path('css/dashboard-sent-bulk.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-sent-checkbox.css').'?v='.filemtime(public_path('css/dashboard-sent-checkbox.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-sent-header.css').'?v='.filemtime(public_path('css/dashboard-sent-header.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace-messages.css').'?v='.filemtime(public_path('css/workspace-messages.css')) }}">
    <script src="{{ asset('js/dashboard-messages.js').'?v='.filemtime(public_path('js/dashboard-messages.js')) }}" defer></script>
</head>
<body class="{{ $bodyClass }}">
    <a class="skip-link" href="#main">Skip to content</a>
    <x-sidebar/>
    <x-screen-time/>
    <main class="main" id="main">
        <x-workspace-global-bar/>
        <x-messages-popup/>
        <div class="content">{{ $slot }}<x-footer/></div>
    </main>
    <x-toasts/>
</body>
</html>
