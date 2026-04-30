<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Converts all JSON response keys from snake_case → camelCase so the mobile
 * app always receives camelCase without any client-side normalisation.
 */
class ConvertResponseKeysToCamelCase
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setData($this->convertKeys($response->getData(true)));
        }

        return $response;
    }

    private function convertKeys(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $converted = [];

        foreach ($data as $key => $value) {
            $converted[is_string($key) ? Str::camel($key) : $key] = $this->convertKeys($value);
        }

        return $converted;
    }
}
