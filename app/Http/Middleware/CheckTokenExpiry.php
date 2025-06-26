<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
       $token = $request->user()?->currentAccessToken();

    // Check if token was created more than 20 minutes ago
    if ($token && $token->created_at->lt(now()->subMinutes(20))) {
        $token->delete(); // Optional: revoke the expired token

        return response()->json([
            'message' => 'Your session has expired. Please log in again.'
        ], 401);
    }

    return $next($request);
    }
}
