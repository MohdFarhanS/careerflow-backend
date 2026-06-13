<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Daftarkan user baru dan langsung login.
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return $user;
    }

    /**
     * Login user dengan kredensial email & password.
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials): User
    {
        if (! Auth::attempt($credentials)) {
            throw new AuthenticationException('Email atau password salah.');
        }

        request()->session()->regenerate();

        return Auth::user();
    }

    /**
     * Logout user aktif.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}