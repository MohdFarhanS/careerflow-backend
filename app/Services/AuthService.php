<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Daftarkan user baru dan terbitkan API token.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return [
            'user'  => $user,
            'token' => $user->createToken('auth')->plainTextToken,
        ];
    }

    /**
     * Login user dengan kredensial email & password, terbitkan API token.
     *
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Email atau password salah.');
        }

        return [
            'user'  => $user,
            'token' => $user->createToken('auth')->plainTextToken,
        ];
    }

    /**
     * Logout user aktif: hapus token aktif (mode token) dan/atau sesi (mode web).
     */
    public function logout(): void
    {
        $user  = request()->user();
        $token = $user?->currentAccessToken();

        // Mode token: hapus hanya token yang dipakai request ini.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        // Backward-compat untuk sesi (mis. tes actingAs / dev same-origin).
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
