<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthProviderInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendPhoneOtpRequest;
use App\Http\Requests\Auth\VerifyPhoneOtpRequest;
use App\Http\Requests\Auth\VerifyResetOtpRequest;
use App\Notifications\PasswordResetOtpNotification;
use App\Http\Resources\UserResource;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

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
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->auth->login($request->email, $request->password);
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

        // Always return 200 — don't reveal whether the email exists
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

        // OTP is valid — replace it with a short-lived reset token
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

        // Reset token expires after 15 minutes
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
