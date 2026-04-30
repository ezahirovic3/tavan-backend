<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthProviderInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendPhoneOtpRequest;
use App\Http\Requests\Auth\VerifyPhoneOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
