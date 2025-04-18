<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name'            => 'nullable|string',
            'description'     => 'nullable|string',
            'video'           => 'nullable|file|mimetypes:video/mp4,video/quicktime|max:10240',
            'is_available'    => 'nullable|boolean',
            'images'          => 'nullable|array',
            'images.*.file'   => 'nullable|file|image|mimes:png,jpg,jpeg,gif|max:10000|mimetypes:image/jpeg,image/png,image/jpg,image/gif',
        ];
    }
}
