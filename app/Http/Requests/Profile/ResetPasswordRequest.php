<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends BaseFormRequest
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
            'current_password' => [
                'required',
                'string',
                'current_password:api'
            ],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'كلمة المرور الحالية',
            'new_password' => 'كلمة المرور الجديدة'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'confirmed' => 'تأكيد كلمة المرور الجديدة غير متطابق.',
            'current_password' => 'كلمة المرور الحالية غير صحيحة.',

            'new_password.min' => 'يجب أن تتكون كلمة المرور الجديدة من 8 أحرف على الأقل.',
            'new_password.mixed_case' => 'يجب أن تحتوي كلمة المرور الجديدة على أحرف كبيرة وصغيرة.',
            'new_password.numbers' => 'يجب أن تحتوي كلمة المرور الجديدة على الأقل على رقم واحد.',
            'new_password.symbols' => 'يجب أن تحتوي كلمة المرور الجديدة على الأقل على رمز واحد.',
            'new_password.uncompromised' => 'كلمة المرور الجديدة ضعيفة جداً أو مسربة. يرجى اختيار كلمة مرور أخرى.'
        ];
    }
}
