<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseFormRequest;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartRequest extends BaseFormRequest
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
        $productId = $this->input('product_id');

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('is_available', true);
                }),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id'),
                function ($attribute, $value, $fail) use ($productId) {
                    if ($value && $productId) {
                        $variant = ProductVariant::find($value);
                        if (!$variant || $variant->product_id != $productId) {
                            $fail('المتغير المختار لا ينتمي للمنتج المحدد.');
                        }
                        if ($variant->quantity < $this->input('quantity', 1)) {
                            $fail("الكمية المطلوبة للمتغير المحدد غير متوفرة في المخزون.");
                        }
                    }
                },
            ],
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
            'product_id.required' => 'حقل المنتج مطلوب.',
            'product_id.integer' => 'معرف المنتج يجب أن يكون رقمًا صحيحًا.',
            'product_id.exists' => 'المنتج المختار غير صالح أو غير متوفر.',
            'quantity.required' => 'حقل الكمية مطلوب.',
            'quantity.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'quantity.min' => 'يجب أن تكون الكمية 1 على الأقل.',
            'product_variant_id.integer' => 'معرف متغير المنتج يجب أن يكون رقمًا صحيحًا.',
            'product_variant_id.exists' => 'متغير المنتج المختار غير صالح.',
        ];
    }
}
