<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActiveAt
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only update for authenticated API users; skip unauthenticated requests
        if ($user = $request->user()) {
            // Throttle writes — only update if last_active_at is null or older than 5 minutes
            if (
                ! $user->last_active_at ||
                $user->last_active_at->diffInMinutes(now()) >= 5
            ) {
                $user->updateQuietly(['last_active_at' => now()]);
            }
        }

        return $response;
    }
}
