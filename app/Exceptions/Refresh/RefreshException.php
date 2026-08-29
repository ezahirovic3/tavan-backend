<?php

namespace App\Exceptions\Refresh;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base for "you can't refresh this listing" failures.
 *
 * Laravel invokes render() on a thrown exception automatically, so these need
 * no registration in bootstrap/app.php. Every response carries a machine
 * readable `code` alongside the human `message`, because the mobile client
 * branches on it (see src/hooks/useRefreshListing.js).
 */
abstract class RefreshException extends RuntimeException
{
    abstract public function errorCode(): string;

    abstract public function statusCode(): int;

    /** Extra machine-readable fields merged into the JSON body. */
    public function context(): array
    {
        return [];
    }

    public function render(Request $request): JsonResponse
    {
        $context = $this->context();

        $response = response()->json(array_merge([
            'message' => $this->getMessage(),
            'code'    => $this->errorCode(),
        ], $context), $this->statusCode());

        if (isset($context['retry_after'])) {
            $response->header('Retry-After', (string) $context['retry_after']);
        }

        return $response;
    }
}
