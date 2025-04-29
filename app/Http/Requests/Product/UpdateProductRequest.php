<?php

namespace App\Http\Requests\Product;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\BaseFormRequest;

class UpdateProductRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        Log::info('Request Data:', $this->all());

        // Force convert is_available to boolean
        if ($this->has('is_available')) {
            $this->merge([
                'is_available' => filter_var($this->input('is_available'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Check if variants is a JSON string and decode it into an array
        if ($this->has('variants') && is_string($this->input('variants'))) {
            // Remove any extra slashes and decode the JSON string into an array
            $variantsString = stripslashes($this->input('variants'));
            $decodedVariants = json_decode($variantsString, true);

            // If decoding is successful, merge the decoded data into the request
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'variants' => $decodedVariants,
                ]);
            } else {
                // Optionally log the error if decoding fails
                Log::error('Invalid JSON string for variants:', [
                    'variants' => $this->input('variants'),
                    'json_last_error' => json_last_error_msg(),
                ]);
            }
        }
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

        Log::info('Request Data:', $this->all());

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
