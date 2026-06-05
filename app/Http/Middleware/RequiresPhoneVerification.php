<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresPhoneVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->phone_verified_at) {
            return response()->json([
                'message' => 'Morate potvrditi broj telefona.',
                'code'    => 'phone_unverified',
            ], 403);
        }

        return $next($request);
    }
}
