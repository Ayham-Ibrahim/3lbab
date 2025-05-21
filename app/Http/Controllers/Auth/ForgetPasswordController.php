<?php

namespace App\Http\Controllers\Auth;

use Exception;
use App\Models\User;
use App\Mail\SendResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Auth\VerifyEmailOtpRequest;
use App\Http\Requests\Auth\VerifyAndChangePasswordRequest;
use App\Http\Requests\Auth\SendEmailVerificationOtpRequest;


class ForgetPasswordController extends Controller
{
    /**
     * Send a verification OTP code to user's email for password reset.
     *
     * @param  \App\Http\Requests\Auth\SendEmailVerificationOtpRequest  $request
     * @return array|null
     */
    public function forgotPassword(SendEmailVerificationOtpRequest $request)
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otp = (string)random_int(10000, 99999);
        $cacheKey = 'password_reset_' . $user->email;
        // Store OTP in cache for 10 minutes
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        try {
        Mail::to($request->email)->send(new SendResetCode($otp, $user->name));
            return ['success' => true, 'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.', 'status_code' => 200];
        } catch (Exception $e) {
            Log::error('Failed to send OTP email for user ID ' . $user->id . ': ' . $e->getMessage());
            $this->throwExceptionJson();
        }
    }

    /**
     * Verify the OTP code and reset the user's password.
     *
     * @param  \App\Http\Requests\Auth\VerifyAndChangePasswordRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyResetCode(VerifyAndChangePasswordRequest $request)
    {
        $data = $request->validated();
        $cacheKey = 'password_reset_' . $data['email'];
        $cachedCode = Cache::get($cacheKey);

        if (!$cachedCode) {
            return response()->json(['message' => 'انتهت صلاحية رمز التحقق'], 422);
        }

        if ($data['code'] != $cachedCode) {
            return response()->json(['message' => 'الرمز غير صحيح'], 422);
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->password = Hash::make($data['password']);
        $user->save();

        Cache::forget($cacheKey);

        return response()->json(['message' => 'تمت عمليةالتحقق بنجاح']);
    }



}
