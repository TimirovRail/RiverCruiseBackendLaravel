<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'role' => 'in:admin,user,manager',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        // Генерация секретного ключа для 2FA
        $twoFactorSecret = $this->google2fa->generateSecretKey();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => trim($request->role ?? 'user'),
            'two_factor_secret' => $twoFactorSecret,
        ]);

        return response()->json([
            'user' => $user,
            'two_factor_secret' => $twoFactorSecret,
        ], 201);
    }
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('api')->user();
        $role = trim($user->role);

        // Если у пользователя есть секретный ключ, возвращаем его и QR-код
        $twoFactorSecret = $user->two_factor_secret;
        $qrCodeUrl = null;

        if ($twoFactorSecret) {
            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $twoFactorSecret
            );
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'role' => $role,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'two_factor_secret' => $twoFactorSecret,
            ],
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = auth('api')->user();
        $code = $request->input('code');

        if (!$user->two_factor_secret) {
            return response()->json(['error' => 'Two-factor authentication not enabled'], 400);
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $code);

        if ($valid) {
            return response()->json(['message' => 'Two-factor authentication verified']);
        }

        return response()->json(['error' => 'Invalid two-factor code'], 401);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'user' => auth('api')->user(),
        ]);
    }
}