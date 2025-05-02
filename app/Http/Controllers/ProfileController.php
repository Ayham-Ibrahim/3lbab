<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\ResetPasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * The service class responsible for handling User-related business logic.
     *
     * @var \App\Services\ProfileService
     */
    protected $profileService;

    /**
     * Create a new ProfileController instance and inject the ProfileService.
     *
     * @param \App\Services\ProfileService $profileService
     */
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Update the user's profile.
     */
    public function update(UpdateProfileRequest $request)
    {
        return $this->success(
            $this->profileService->update($request->validated()),
            'Profile updated successfully'
        );
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->success(
            $this->profileService->resetPassword($request->validated()),
            'Password reseted successfully'
        );
    }
}
