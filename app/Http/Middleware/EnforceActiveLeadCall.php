<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceActiveLeadCall
{
    public function handle(Request $request, Closure $next): Response
    {
        // Calls are non-blocking. Clear obsolete session markers left by the
        // former modal lock, then allow all normal lead actions to continue.
        $request->session()->forget('active_lead_call_id');
        return $next($request);
    }
}
