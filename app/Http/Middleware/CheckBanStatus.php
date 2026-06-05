<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBanStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            return response()->json([
                'message' => 'Tvoj račun je suspendiran.',
                'code'    => 'account_banned',
            ], 403);
        }

        return $next($request);
    }
}
