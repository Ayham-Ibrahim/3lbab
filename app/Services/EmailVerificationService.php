<?php

namespace App\Services;

use App\Models\User;
use App\Mail\SendOtpEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

/**
 * Service class responsible for handling the business logic of email verification using OTPs.
 * This includes generating, caching, sending, and verifying OTPs.
 * Assumes it extends a base `Service` class that might provide `throwExceptionJson()`.
 */
class EmailVerificationService extends Service
{
    /**
     * The duration in minutes for which the OTP will be valid.
     * @var int
     */
    protected int $otpExpirationMinutes = 10;

    /**
     * Generates, caches, and sends an OTP to the user's email address.
     * The user is identified by the 'email' provided in the data array.
     *
     * @param array $data An array containing the 'email' of the user. Example: ['email' => 'user@example.com']
     * @return array An array with 'success' (bool), 'message' (string), and 'status_code' (int).
     *               Example success: ['success' => true, 'message' => 'Verification code sent to your email.', 'status_code' => 200]
     *               Example already verified: ['success' => true, 'message' => 'Email is already verified.', 'status_code' => 200]
     *               Throws an exception via $this->throwExceptionJson() on mail sending failure if that method exists and is used.
     */
    public function sendOtp(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if ($user->hasVerifiedEmail()) {
            return ['success' => true, 'message' => 'البريد الإلكتروني مُحقق بالفعل.', 'status_code' => 200];
        }

        $otp = (string)random_int(10000, 99999);
        $otpKey = 'email_verification_otp_' . $user->id;

        Cache::put($otpKey, $otp, now()->addMinutes($this->otpExpirationMinutes));

        try {
            Mail::to($user->email)->send(new SendOtpEmail($otp, $user->name));
            return ['success' => true, 'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.', 'status_code' => 200];
        } catch (Exception $e) {
            Log::error('Failed to send OTP email for user ID ' . $user->id . ': ' . $e->getMessage());
            $this->throwExceptionJson();
        }
    }

    /**
     * Verifies the submitted OTP against the cached one for the given user's email.
     * Marks the email as verified upon successful OTP validation.
     * The user is identified by the 'email' provided in the data array.
     *
     * @param array $data An array containing the 'email' of the user and the 'otp' submitted.
     *                    Example: ['email' => 'user@example.com', 'otp' => '12345']
     * @return array An array with 'success' (bool), 'message' (string), and 'status_code' (int).
     *               Example success: ['success' => true, 'message' => 'Email verified successfully.', 'status_code' => 200]
     *               Example failure: ['success' => false, 'message' => 'Invalid or expired OTP.', 'status_code' => 400]
     */
    public function verifyOtp(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if ($user->hasVerifiedEmail()) {
            return ['success' => true, 'message' => 'البريد الإلكتروني مُحقق بالفعل.', 'status_code' => 200];
        }

        $otpKey = 'email_verification_otp_' . $user->id;
        $cachedOtp = Cache::get($otpKey);

        if (!$cachedOtp) {
            return ['success' => false, 'message' => 'رمز التحقق غير صالح أو انتهت صلاحيته.', 'status_code' => 400];
        }

        if ($cachedOtp === $data['otp']) {
            $user->markEmailAsVerified();
            Cache::forget($otpKey);
            return [
                'data' => [
                    'user'        => $user,
                    'is_verified' => true,
                    'token'       => $user->createToken('auth_token')->plainTextToken
                ],
                'success' => true,
                'message' => 'تم التحقق من البريد الإلكتروني بنجاح.',
                'status_code' => 200
            ];
        } else {
            return ['success' => false, 'message' => 'رمز التحقق غير صحيح.', 'status_code' => 400];
        }
    }
}
