<?php

namespace App\Http\Requests\Complaint;

use App\Http\Requests\BaseFormRequest;

class StoreComplaintRequest extends BaseFormRequest
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
            'manager_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],
            'content' => [
                'required',
                'string',
                'max:1000'
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/jpeg,image/png,image/jpg',
                'max:10000'
            ],
            'phone' => [
                'required',
                'string',
                'max:255',
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
            'manager_id' => 'المدير',
            'content' => 'محتوى الشكوى',
            'image' => 'الصورة',
            'phone' => 'رقم الموبايل'
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
            'exists' => 'قيمة :attribute غير موجودة.',

            'image.image' => 'حقل الصورة يجب أن يكون صورة.',
            'image.mimes' => 'الصورة يجب أن تكون من نوع: png, jpg, jpeg.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 10 ميجابايت.',
            'image.mimetypes' => 'نوع ملف الصورة غير مسموح به. الأنواع المسموحة: image/jpeg, image/png, image/jpg.',
        ];
    }
}
