<x-auth-layout title="Welcome back">
    <p>Sign in to manage your freight operations.</p>
    <form method="POST" action="{{ route('login.store') }}" class="auth-form">@csrf
        <label for="email">Email address or phone number<input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" maxlength="255"></label>
        <label for="password">Password<input id="password" type="password" name="password" required autocomplete="current-password" maxlength="255"></label>
        <p class="auth-note">Need help signing in? Contact your administrator.</p>
        <button class="button primary" type="submit">Sign in</button>
    </form>
    <p class="auth-note">For your security, sign-in expires at midnight (India time) or after 30 minutes without activity.</p>
</x-auth-layout>
