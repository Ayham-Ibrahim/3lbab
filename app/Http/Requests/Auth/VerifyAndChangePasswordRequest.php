<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAndChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed'

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

            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.string' => 'كلمة المرور يجب أن تكون نصًا.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}
