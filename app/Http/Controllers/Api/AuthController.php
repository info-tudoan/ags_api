<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController
{
    public function __construct(protected MfaService $mfaService) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone' => 'nullable|string',
            'role' => 'required|in:employee,team_lead,admin,hr',
            'employee_id' => 'nullable|string|unique:users',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';
        $validated['mfa_enabled'] = false;

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();

        if ($user->mfa_enabled) {
            return response()->json([
                'message' => 'MFA required',
                'mfa_required' => true,
                'user_id' => $user->id,
            ]);
        }

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function mfaSetup(Request $request): JsonResponse
    {
        $user = auth()->user();
        $secret = $this->mfaService->generateSecret();
        $qrCodeUrl = $this->mfaService->getQRCodeUrl($user->email, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'message' => 'Scan QR code with authenticator app',
        ]);
    }

    public function mfaVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|numeric',
            'secret' => 'required|string',
        ]);

        if (!$this->mfaService->verifyToken($validated['secret'], $validated['token'])) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = auth()->user();
        $user->update([
            'mfa_enabled' => true,
            'mfa_secret' => encrypt($validated['secret']),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'MFA enabled successfully',
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function mfaVerifyLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'token' => 'required|numeric',
        ]);

        $user = User::find($validated['user_id']);

        if (!$user->mfa_enabled) {
            return response()->json(['message' => 'MFA not enabled'], 400);
        }

        $secret = decrypt($user->mfa_secret);

        if (!$this->mfaService->verifyToken($secret, $validated['token'])) {
            return response()->json(['message' => 'Invalid MFA token'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'MFA verification successful',
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'user' => $user,
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh();

        return response()->json([
            'token' => $token,
        ]);
    }
}
