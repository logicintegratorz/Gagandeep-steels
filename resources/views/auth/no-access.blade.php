<x-auth-layout title="Your account is ready">
    <x-session/>
    <p>Your role has no page access yet. Ask an administrator to assign the required permissions.</p>
    <form method="POST" action="{{ route('logout') }}" class="auth-form">@csrf<button class="button secondary">Sign out</button></form>
</x-auth-layout>
