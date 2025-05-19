<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class VerifyEmailOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'otp' => 'required|numeric|digits:5',
            'email' => 'required|email|exists:users,email'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'otp.required' => 'حقل رمز التحقق مطلوب.',
            'otp.numeric' => 'يجب أن يكون رمز التحقق رقمًا.',
            'otp.digits' => 'يجب أن يتكون رمز التحقق من 5 أرقام.',

            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'يجب أن يكون البريد الإلكتروني عنوان بريد إلكتروني صالحًا.',
            'email.exists' => 'هذا البريد الإلكتروني غير مسجل لدينا أو لا يتطابق مع حسابك.',
        ];
    }
}
