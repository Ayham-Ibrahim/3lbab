<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends BaseFormRequest
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
            'category_id'     => 'nullable|integer|exists:categories,id',
            'store_id'        => 'nullable|integer|exists:stores,id',
            'price'           => 'nullable|numeric',
            'name'            => [
                'nullable',
                'string',
                Rule::unique('products')->ignore($this->product)
            ],
            'description'     => 'nullable|string',
            'video'           => 'nullable|file|mimetypes:video/mp4,video/quicktime|max:10240',
            'is_available'    => 'nullable|boolean',
            'images'          => 'nullable|array',
            'images.*.file'   => 'nullable|file|image|mimes:png,jpg,jpeg|max:10000|mimetypes:image/jpeg,image/png,image/jpg',
            'variants'        => 'sometimes|array',
            'variants.*.id'   => 'nullable|integer|exists:product_variants,id',
            'variants.*.color_id' => [
                'nullable',
                'integer',
                'exists:colors,id',
            ],
            'variants.*.size_id' => [
                'nullable',
                'integer',
                'exists:sizes,id',
            ],
            'variants.*.quantity' => 'required|integer|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $product = $this->route('product');
            $variants = $this->input('variants', []);

            if (empty($variants)) {
                return;
            }

            if ($product->variants()->exists()) {
                $dbVariant = $product->variants()->first();
                $hasColor = !is_null($dbVariant->color_id);
                $hasSize = !is_null($dbVariant->size_id);
            } else {
                $firstVariant = $variants[0];
                $hasColor     = !is_null($firstVariant['color_id'] ?? null);
                $hasSize      = !is_null($firstVariant['size_id'] ?? null);
            }

            foreach ($variants as $index => $variant) {
                $currentHasColor = !is_null($variant['color_id'] ?? null);
                $currentHasSize = !is_null($variant['size_id'] ?? null);

                if ($currentHasColor !== $hasColor || $currentHasSize !== $hasSize) {
                    $validator->errors()->add(
                        "variants.$index",
                        'جميع المتغيرات يجب أن تتبع نفس النمط (لون و/أو مقاس)'
                    );
                }
            }

            if (!empty($variants) && ($this->input('color_id') || $this->input('size_id'))) {
                $validator->errors()->add(
                    'variants',
                    'لا يمكن أن يحتوي المنتج على لون/مقاس رئيسي إذا كان لديه variants'
                );
            }
        });
    }
}
