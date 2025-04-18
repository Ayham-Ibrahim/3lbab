<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Login an existing user.
     *
     * @param array $credentials
     * @return array|bool
     */
    public function login(array $credentials)
    {
        // Find the user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return false; // Return false for invalid credentials
        }

        // Generate a Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Logout the authenticated user.
     *
     * @return void
     */
    public function logout()
    {
        Auth::user()->tokens()->delete();
    }
}
