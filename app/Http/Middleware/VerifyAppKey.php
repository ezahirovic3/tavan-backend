<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('app.client_key');

        if (empty($expected)) {
            return $next($request);
        }

        if ($request->header('X-App-Key') !== $expected) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
