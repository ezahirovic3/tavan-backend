<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthRedirectController extends Controller
{
    /**
     * GET /api/v1/auth/social/google/redirect
     *
     * Redirects the browser to Google's OAuth consent screen.
     * The mobile app opens this URL via WebBrowser.openAuthSessionAsync().
     */
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * GET /api/v1/auth/social/google/callback
     *
     * Google redirects here after the user authenticates.
     * We find or create the user, issue a Sanctum token, then redirect
     * back to the app via the deep link scheme: tavan://auth?token=...
     */
    public function callback(): \Illuminate\Http\RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect('tavan://auth?error=google_auth_failed');
        }

        $user = $this->findOrCreateUser($googleUser);
        $token = $user->createToken('mobile')->plainTextToken;

        return redirect('tavan://auth?token=' . urlencode($token));
    }

    private function findOrCreateUser(\Laravel\Socialite\Contracts\User $googleUser): User
    {
        // 1. Find by Google ID
        $user = User::where('google_id', $googleUser->getId())->first();
        if ($user) {
            return $user;
        }

        // 2. Find by email and link Google ID
        if ($googleUser->getEmail()) {
            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->update([
                    'google_id'          => $googleUser->getId(),
                    'email_verified_at'  => $user->email_verified_at ?? now(),
                ]);
                return $user;
            }
        }

        // 3. Create new user
        $givenName = $googleUser->offsetGet('given_name') ?? null;
        $base      = $givenName
            ? Str::lower(preg_replace('/[^a-zA-Z0-9_.]/', '', $givenName))
            : 'korisnik';
        $base      = $base ?: 'korisnik';
        $username  = $base . rand(1000, 9999);

        while (User::where('username', $username)->exists()) {
            $username = $base . rand(1000, 9999);
        }

        return User::create([
            'google_id'          => $googleUser->getId(),
            'name'               => $googleUser->getName() ?? 'Tavan korisnik',
            'username'           => $username,
            'email'              => $googleUser->getEmail(),
            'password'           => bcrypt(Str::random(32)),
            'profile_setup_done' => false,
            'email_verified_at'  => now(),
        ]);
    }
}
