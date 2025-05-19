<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')->id;

        return [
            'name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password'  => [
                'nullable',
                'string',
                'min:8',
                'confirmed'
            ],
            'role' => [
                'nullable',
                'string',
                'in:admin,storeManager,customer'
            ],
            'photo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/jpeg,image/png,image/jpg',
                'max:10000'
            ],
            'location' => [
                'nullable',
                'string',
                'max:255'
            ],
            'whatsAppNumber' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('user_infos', 'whatsAppNumber')->ignore($userId, 'user_id'),

            ],
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
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'role' => 'الدور',
            'photo' => 'الصورة',
            'location' => 'الموقع',
            'whatsAppNumber' => 'رقم الواتساب'
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
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرف/أحرف.',
            'email' => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صالحاً.',
            'unique' => 'قيمة :attribute مسجلة مسبقاً.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min حرف/أحرف.',

            'password.required' => 'حقل كلمة المرور مطلوب.',
            'password.string' => 'حقل كلمة المرور يجب أن يكون نصاً.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن :min أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',

            'role.required' => 'حقل الدور مطلوب.',
            'role.string' => 'حقل الدور يجب أن يكون نصاً.',
            'role.in' => 'قيمة حقل الدور غير صالحة. القيم المسموحة هي: admin, storeManager, customer.',

            'photo.image' => 'حقل :attribute يجب أن يكون صورة.',
            'photo.mimes' => 'الصورة يجب أن تكون من نوع: :values.',
            'photo.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت (ما يعادل 10 ميجابايت).',
            'photo.mimetypes' => 'نوع ملف الصورة غير مسموح به. الأنواع المسموحة: :values.',

            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'whatsAppNumber.unique' => 'رقم الواتساب هذا مستخدم بالفعل.'
        ];
    }
}
