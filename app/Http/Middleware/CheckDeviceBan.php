<?php

namespace App\Http\Middleware;

use App\Models\BannedDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDeviceBan
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->header('X-Device-ID');

        if ($deviceId && BannedDevice::where('device_id', $deviceId)->exists()) {
            return response()->json([
                'message' => 'Registracija nije moguća s ovog uređaja.',
                'code'    => 'device_banned',
            ], 403);
        }

        return $next($request);
    }
}
