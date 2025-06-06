<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends BaseFormRequest
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
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories')->ignore($this->category)
            ],
            'image' => 'nullable|file|image|mimes:png,jpg,jpeg|max:10000|mimetypes:image/jpeg,image/png,image/jpg',
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
            'name' => 'اسم الفئة',
            'image' => 'صورة الفئة',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'يجب أن يكون اسم الفئة نصياً',
            'name.max' => 'يجب ألا يتجاوز اسم الفئة 255 حرفاً',
            'name.unique' => 'هذا الاسم مستخدم بالفعل لفئة أخرى',

            'image.file' => 'يجب أن تكون الصورة ملفاً',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'يجب أن تكون الصورة من نوع: png, jpg, jpeg, gif',
            'image.max' => 'يجب ألا تتجاوز حجم الصورة 10 ميجابايت',
            'image.mimetypes' => 'يجب أن تكون الصورة من نوع: jpeg, png, jpg, gif',
        ];
    }
}
