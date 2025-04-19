<?php

namespace App\Http\Requests\Color;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateColorRequest extends BaseFormRequest
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
                Rule::unique('colors')->ignore($this->color)
            ],
            'hex_code' => [
                'nullable',
                'string',
                'max:7',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
                Rule::unique('colors')->ignore($this->color)
            ],
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
            'name.string' => 'اسم اللون يجب أن يكون نصاً',
            'name.max' => 'اسم اللون يجب ألا يتجاوز 255 حرفاً',
            'name.unique' => 'اسم اللون مستخدم مسبقاً',

            'hex_code.string' => 'كود اللون يجب أن يكون نصاً',
            'hex_code.max' => 'كود اللون يجب ألا يتجاوز 7 أحرف',
            'hex_code.regex' => 'صيغة كود اللون غير صالحة (يجب أن يكون بالشكل #FFFFFF أو #FFF)',
            'hex_code.unique' => 'كود اللون مستخدم مسبقاً',
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
            'name' => 'اسم اللون',
            'hex_code' => 'كود اللون',
        ];
    }
}
