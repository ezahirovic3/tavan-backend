<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Converts all incoming request parameter keys from camelCase to snake_case.
 *
 * This lets the mobile app send camelCase params (e.g. rootCategory, minPrice)
 * without each controller/route needing its own mapping. Values are untouched —
 * only keys are transformed, and nested arrays are handled recursively.
 */
class ConvertCamelToSnakeCase
{
    public function handle(Request $request, Closure $next)
    {
        // Query string (GET params)
        $request->query->replace($this->convertKeys($request->query->all()));

        // Request body (POST / PATCH / PUT)
        if ($request->isJson()) {
            $request->json()->replace($this->convertKeys($request->json()->all()));
        } else {
            $request->request->replace($this->convertKeys($request->request->all()));
        }

        return $next($request);
    }

    private function convertKeys(array $data): array
    {
        $converted = [];

        foreach ($data as $key => $value) {
            $converted[Str::snake($key)] = is_array($value)
                ? $this->convertKeys($value)
                : $value;
        }

        return $converted;
    }
}
