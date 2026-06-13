<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        Auth::login($user);

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = $this->authService->login($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function user(Request $request)
    {
        return new UserResource($request->user());
    }
}