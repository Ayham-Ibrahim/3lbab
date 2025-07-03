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

        $user->assignRole('customer');

        return [
            'user' => $user
        ];
    }

    /**
     * Login an existing user.
     *
     * @param array $credentials
     * 
     */
    public function login(array $credentials)
    {
        // Find the user by email
        $user = User::where('email', $credentials['email'])->first();

         // Check if user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return 'invalid_credentials'; // return specific error
        }

        // ✅ Check if user is available
        if (!$user->is_available) {
            return 'account_disabled'; // return specific error
        }

        //load info
        $user->load('info');

        if ($user->email_verified_at) {
            $is_verified = true;
            $token = $user->createToken('auth_token')->plainTextToken;
        } else {
            $is_verified = false;
            $token = null;
        }


        return [
            'user'        => $user,
            'is_verified' => $is_verified,
            'token'       => $token
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
