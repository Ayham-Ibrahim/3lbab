<?php

namespace App\Http\Requests\Size;

use App\Http\Requests\BaseFormRequest;

class StoreSizeRequest extends BaseFormRequest
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
            'size_code' => [
                'required',
                'string',
                'max:20',
                'unique:sizes,size_code'
            ]
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'size_code.required' => 'كود المقاس مطلوب',
            'size_code.string' => 'كود المقاس يجب أن يكون نصاً',
            'size_code.max' => 'كود المقاس يجب ألا يتجاوز 20 حرفاً',
            'size_code.unique' => 'كود المقاس مسجل مسبقاً'
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
            'type' => 'نوع المقاس',
            'size_code' => 'كود المقاس'
        ];
    }
}
