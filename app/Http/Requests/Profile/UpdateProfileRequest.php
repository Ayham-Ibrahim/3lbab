<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseFormRequest
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
        $user = $this->user();

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
                Rule::unique('users')->ignore($user->id)
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
                Rule::unique('user_infos', 'whatsAppNumber')->ignore($user->info?->id)
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
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرف/أحرف.',
            'email' => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صالحاً.',
            'unique' => 'قيمة :attribute مسجلة مسبقاً.',

            'photo.image' => 'حقل الصورة يجب أن يكون صورة.',
            'photo.mimes' => 'الصورة يجب أن تكون من نوع: png, jpg, jpeg.',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 10 ميجابايت.',
            'photo.mimetypes' => 'نوع ملف الصورة غير مسموح به. الأنواع المسموحة: image/jpeg, image/png, image/jpg.',
        ];
    }
}
