<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthProviderInterface;
use App\Exceptions\EmailNotVerifiedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendPhoneOtpRequest;
use App\Http\Requests\Auth\VerifyPhoneOtpRequest;
use App\Http\Requests\Auth\VerifyResetOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use App\Notifications\PasswordResetOtpNotification;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthProviderInterface $auth,
        private readonly PhoneVerificationService $phoneVerification,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return response()->json([
            'data' => [
                'status' => $result['status'],
                'email'  => $result['email'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->auth->login($request->email, $request->password);
        } catch (EmailNotVerifiedException $e) {
            return response()->json([
                'message' => 'Email adresa nije potvrđena.',
                'code'    => 'email_not_verified',
                'email'   => $e->email,
            ], 403);
        } catch (AuthenticationException) {
            return response()->json(['message' => 'Pogrešni podaci za prijavu.'], 401);
        }

        return response()->json([
            'data' => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $record = DB::table('email_verification_tokens')->where('email', $request->email)->first();

        if (! $record) {
            return response()->json(['message' => 'Nevažeći zahtjev.'], 422);
        }

        if ((now()->timestamp - \Carbon\Carbon::parse($record->created_at)->timestamp) > 900) {
            DB::table('email_verification_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Kod je istekao. Zatraži novi.'], 422);
        }

        if (! Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'Pogrešan kod. Provjeri email i pokušaj ponovo.'], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['email_verified_at' => now()]);
        DB::table('email_verification_tokens')->where('email', $request->email)->delete();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function resendEmailVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $record = DB::table('email_verification_tokens')->where('email', $request->email)->first();

        if ($record) {
            $secondsAgo = now()->timestamp - \Carbon\Carbon::parse($record->sent_at)->timestamp;
            if ($secondsAgo < 60) {
                return response()->json([
                    'message'           => 'Sačekaj malo prije ponovnog slanja.',
                    'seconds_remaining' => max(0, 60 - $secondsAgo),
                ], 429);
            }
        }

        $user = User::where('email', $request->email)->whereNull('email_verified_at')->first();

        if (! $user) {
            return response()->json(['message' => 'Zahtjev nije validan.'], 422);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($otp), 'sent_at' => now(), 'created_at' => now()],
        );

        $user->notify(new EmailVerificationOtpNotification($otp));

        return response()->json(['message' => 'Novi kod je poslan.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['message' => 'Uspješno odjavljen.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Ako postoji račun sa tom adresom, poslaćemo ti kod za reset.']);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($otp), 'created_at' => now()],
        );

        $user->notify(new PasswordResetOtpNotification($otp));

        return response()->json(['message' => 'Ako postoji račun sa tom adresom, poslaćemo ti kod za reset.']);
    }

    public function verifyResetOtp(VerifyResetOtpRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Nevažeći ili istekao kod.'], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Kod je istekao. Zatraži novi.'], 422);
        }

        if (! Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'Nevažeći kod. Provjeri email i pokušaj ponovo.'], 422);
        }

        $resetToken = Str::uuid()->toString();

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->update(['token' => Hash::make($resetToken), 'created_at' => now()]);

        return response()->json(['data' => ['resetToken' => $resetToken]]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Nevažeći ili istekao zahtjev.'], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Zahtjev je istekao. Počni ispočetka.'], 422);
        }

        if (! Hash::check($request->resetToken, $record->token)) {
            return response()->json(['message' => 'Nevažeći zahtjev za reset.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'Korisnik nije pronađen.'], 422);
        }

        $user->update(['password' => Hash::make($request->newPassword)]);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Lozinka je uspješno resetovana. Možeš se prijaviti.']);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->currentPassword, $user->password)) {
            return response()->json([
                'message' => 'Trenutna lozinka nije ispravna.',
                'errors'  => ['currentPassword' => ['Trenutna lozinka nije ispravna.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($request->newPassword)]);

        return response()->json(['message' => 'Lozinka je uspješno promijenjena.']);
    }

    public function sendPhoneOtp(SendPhoneOtpRequest $request): JsonResponse
    {
        $this->phoneVerification->sendOtp($request->phone);

        return response()->json(['message' => 'Verifikacijski kod je poslan.']);
    }

    public function verifyPhoneOtp(VerifyPhoneOtpRequest $request): JsonResponse
    {
        $verified = $this->phoneVerification->verify($request->phone, $request->otp);

        if (! $verified) {
            return response()->json(['message' => 'Neispravan ili istekao kod.'], 422);
        }

        $this->phoneVerification->markVerified($request->user(), $request->phone);

        return response()->json([
            'data' => ['user' => new UserResource($request->user()->fresh())],
        ]);
    }
}
