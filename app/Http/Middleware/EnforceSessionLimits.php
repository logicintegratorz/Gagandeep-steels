<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class EnforceSessionLimits
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user()?->fresh('role');
        if ($user) Auth::setUser($user);
        $now = now()->timestamp;
        $last = (int) $request->session()->get('last_activity_at', 0);
        $midnight = (int) $request->session()->get('login_expires_at', 0);
        $idleTimeout = max(1, (int) ($user?->inactivity_timeout_minutes ?: 30)) * 60;
        if (!$user || !$user->active || !$last || !$midnight || $now >= $midnight || $now - $last >= $idleTimeout ||
            $user->session_version !== (int) $request->session()->get('session_version', 0)) {
            Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            $message = 'Your session expired due to inactivity. Please sign in again.';
            if ($request->expectsJson()) return response()->json(['message' => $message], 401)->header('Cache-Control', 'no-store');
            return redirect()->route('login')->with('status', $message);
        }
        // Polling checks expiry only; it must never keep an idle user logged in.
        if (!$request->routeIs('session.status', 'notifications.count', 'leads.score', 'screen-time.heartbeat')) $request->session()->put('last_activity_at', $now);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
