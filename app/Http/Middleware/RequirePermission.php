<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

final class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        foreach ($permissions as $permission) {
            if ($request->user()?->allows($permission)) return $next($request);
        }
        abort(403, 'Your role does not have permission for this action.');
    }
}
