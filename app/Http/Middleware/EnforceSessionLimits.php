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
        if (!$user || !$user->active || !$last || !$midnight || $now >= $midnight || $now - $last >= 1800 ||
            $user->session_version !== (int) $request->session()->get('session_version', 0)) {
            Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            if ($request->expectsJson()) return response()->json(['message' => 'Your session expired. Please sign in again.'], 401)->header('Cache-Control', 'no-store');
            return redirect()->route('login')->with('status', 'Your session expired. Please sign in again.');
        }
        // Polling checks expiry only; it must never keep an idle user logged in.
        if (!$request->routeIs('session.status', 'notifications.count', 'leads.score', 'screen-time.heartbeat')) $request->session()->put('last_activity_at', $now);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
