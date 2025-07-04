<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;


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
                'min:8',
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
            'new_password' => 'كلمة المرور الجديدة',
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
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $password = $this->input('new_password');

            if ($password) {
                $hasUpper = preg_match('/[A-Z]/', $password);
                $hasLower = preg_match('/[a-z]/', $password);
                $hasNumber = preg_match('/[0-9]/', $password);
                $hasSymbol = preg_match('/[\W_]/', $password);

                if (!($hasUpper && $hasLower && $hasNumber && $hasSymbol)) {
                    $validator->errors()->add(
                        'new_password',
                        'يجب أن تحتوي كلمة المرور على: (حرف كبير - حرف صغير - رقم - رمز)واحد على الأقل.'
                    );
                }
            }
        });
    }
}
