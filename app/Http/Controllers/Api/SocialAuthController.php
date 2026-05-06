<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    // ── Google ────────────────────────────────────────────────────────────────

    public function google(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        // Verify the ID token with Google's tokeninfo endpoint
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if (! $response->successful()) {
            return response()->json(['message' => 'Nevažeći Google token.'], 401);
        }

        $payload = $response->json();

        // Validate audience — must be one of our client IDs
        $validAudiences = [
            config('services.google.ios_client_id'),
            config('services.google.android_client_id'),
        ];

        if (! in_array($payload['aud'] ?? '', $validAudiences, true)) {
            return response()->json(['message' => 'Nevažeći Google token.'], 401);
        }

        $googleId = $payload['sub'] ?? null;
        $email    = $payload['email'] ?? null;

        if (! $googleId || ! $email) {
            return response()->json(['message' => 'Google nije vratio potrebne podatke.'], 422);
        }

        $user = $this->findOrCreateUser(
            provider: 'google',
            providerId: $googleId,
            email: $email,
            name: $payload['name'] ?? null,
            givenName: $payload['given_name'] ?? null,
        );

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    // ── Apple ─────────────────────────────────────────────────────────────────

    public function apple(Request $request): JsonResponse
    {
        $request->validate([
            'identity_token' => ['required', 'string'],
            'given_name'     => ['sometimes', 'nullable', 'string'],
            'family_name'    => ['sometimes', 'nullable', 'string'],
        ]);

        try {
            $payload = $this->verifyAppleToken($request->identity_token);
        } catch (\Throwable $e) {
            Log::warning('[SocialAuth] Apple token verification failed: ' . $e->getMessage());
            return response()->json(['message' => 'Nevažeći Apple token.'], 401);
        }

        $appleId = $payload->sub ?? null;
        $email   = $payload->email ?? null;

        if (! $appleId) {
            return response()->json(['message' => 'Apple nije vratio potrebne podatke.'], 422);
        }

        $givenName  = $request->given_name;
        $familyName = $request->family_name;
        $name       = trim(($givenName ?? '') . ' ' . ($familyName ?? '')) ?: null;

        $user = $this->findOrCreateUser(
            provider: 'apple',
            providerId: $appleId,
            email: $email,
            name: $name,
            givenName: $givenName,
        );

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function verifyAppleToken(string $identityToken): object
    {
        // Fetch Apple's public keys
        $keysResponse = Http::get('https://appleid.apple.com/auth/keys');
        $keys = $keysResponse->json();

        $publicKeys = JWK::parseKeySet($keys);

        $decoded = JWT::decode($identityToken, $publicKeys);

        // Validate issuer and audience
        if (($decoded->iss ?? '') !== 'https://appleid.apple.com') {
            throw new \RuntimeException('Invalid issuer.');
        }

        if (($decoded->aud ?? '') !== config('services.apple.client_id')) {
            throw new \RuntimeException('Invalid audience.');
        }

        return $decoded;
    }

    private function findOrCreateUser(
        string $provider,
        string $providerId,
        ?string $email,
        ?string $name,
        ?string $givenName,
    ): User {
        // 1. Find by provider ID
        $user = User::where($provider . '_id', $providerId)->first();
        if ($user) {
            return $user;
        }

        // 2. Find by email and link the provider
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update([$provider . '_id' => $providerId]);
                return $user;
            }
        }

        // 3. Create a new user
        $base     = $givenName
            ? Str::lower(preg_replace('/[^a-zA-Z0-9_.]/', '', $givenName))
            : 'korisnik';
        $base     = $base ?: 'korisnik';
        $username = $base . rand(1000, 9999);

        // Ensure username is unique
        while (User::where('username', $username)->exists()) {
            $username = $base . rand(1000, 9999);
        }

        return User::create([
            $provider . '_id'    => $providerId,
            'name'               => $name ?? 'Tavan korisnik',
            'username'           => $username,
            'email'              => $email,
            'password'           => bcrypt(Str::random(32)), // unusable password
            'profile_setup_done' => false,
        ]);
    }
}
