<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendEmailVerificationOtpRequest;
use App\Http\Requests\Auth\VerifyEmailOtpRequest;
use App\Services\EmailVerificationService;
/**
 * Handles HTTP requests related to email verification via OTP.
 * It orchestrates the process by using EmailVerificationService.
 */
class EmailVerificationController extends Controller
{
    /**
     * The service instance for handling email verification logic.
     * @var EmailVerificationService
     */
    protected EmailVerificationService $emailVerificationService;

    /**
     * EmailVerificationController constructor.
     *
     * @param EmailVerificationService $emailVerificationService The service to handle email verification logic.
     */
    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    /**
     * Sends an OTP (One-Time Password) to the user's email for verification.
     *
     * The request must contain a validated 'email' field.
     * The authenticated user is implicitly determined by the 'auth:api' middleware.
     * The `SendEmailVerificationOtpRequest` ensures the provided email matches the authenticated user.
     *
     * @param SendEmailVerificationOtpRequest $request The request object containing validated data,
     *                                               including the 'email' to send the OTP to.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the outcome of the OTP sending process.
     */
    public function sendOtp(SendEmailVerificationOtpRequest $request)
    {
        $result = $this->emailVerificationService->sendOtp($request->validated());

        // Assuming 'success' and 'error' methods exist in your base Controller
        if (method_exists($this, 'success')) {
            if ($result['success']) {
                return $this->success(null, $result['message'], $result['status_code']);
            }
            // Pass an empty array for data if the error method expects it
            return $this->error($result['message'], [], $result['status_code']);
        }
    }

    /**
     * Verifies the submitted OTP and marks the user's email as verified if the OTP is correct.
     *
     * The request must contain validated 'email' and 'otp' fields.
     * The authenticated user is implicitly determined.
     * The `VerifyEmailOtpRequest` ensures the provided email matches the authenticated user.
     *
     * @param VerifyEmailOtpRequest $request The request object containing validated data,
     *                                     including the 'email' and the 'otp' to verify.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the outcome of the OTP verification.
     */
    public function verifyOtp(VerifyEmailOtpRequest $request)
    {
        $result = $this->emailVerificationService->verifyOtp($request->validated());

        if (method_exists($this, 'success')) {
            if ($result['success']) {
                return $this->success(null, $result['message'], $result['status_code']);
            }
            return $this->error($result['message'], [], $result['status_code']);
        }
    }
}
