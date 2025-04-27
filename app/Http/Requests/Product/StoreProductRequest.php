<?php

namespace App\Http\Requests\Product;

use Illuminate\Support\Facades\Log;
use App\Http\Requests\BaseFormRequest;

class StoreProductRequest extends BaseFormRequest
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
            'category_id'     => 'required|integer|exists:categories,id',
            'store_id'        => 'required|integer|exists:stores,id',
            'price'           => 'required|numeric',
            'name'            => 'required|string|unique:products,name',
            'description'     => 'required|string',
            'video'           => 'nullable|file|mimetypes:video/mp4,video/quicktime|max:10240',
            'is_available'    => 'required|boolean',
            'images'          => 'required|array',
            'images.*.file'   => 'required|file|image|mimes:png,jpg,jpeg|max:10000|mimetypes:image/jpeg,image/png,image/jpg',
            'variants'        => 'required|array',
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
    

    protected function prepareForValidation()
    {
        Log::info('Request Data:', $this->all());

        // Force convert is_available to boolean
        if ($this->has('is_available')) {
            $this->merge([
                'is_available' => filter_var($this->input('is_available'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // // // Convert variants if it is stringified
        // if ($this->has('variants') && is_string($this->variants)) {
        //     $this->merge([
        //         'variants' => json_decode($this->variants, true),
        //     ]);
        // }

    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $variants = $this->input('variants', []);

            if (empty($variants)) {
                return;
            }

            $firstVariant = $variants[0];
            $hasColor     = !is_null($firstVariant['color_id'] ?? null);
            $hasSize      = !is_null($firstVariant['size_id'] ?? null);

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
